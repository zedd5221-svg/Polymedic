<?php

namespace App\Controllers;

use App\Models\InvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\PaymentModel;
use App\Models\PatientModel;
use App\Models\DiagnosticRequestModel;
use App\Models\ServiceModel;

class Billing extends BaseController
{
    // =============================================================
    // AUTH + ROLE
    // =============================================================

    private function checkAuth(array $allowedRoles = ['admin', 'receptionist'])
    {
        if (!session()->get('is_logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $role = (string) session()->get('role');
        if (!in_array($role, $allowedRoles, true)) {
            if ($role === 'admin')        return redirect()->to(base_url('admin/dashboard'));
            if ($role === 'receptionist') return redirect()->to(base_url('receptionist/dashboard'));
            if ($role === 'medtech')      return redirect()->to(base_url('medtech/dashboard'));
            if ($role === 'radiologist')  return redirect()->to(base_url('radiologist/dashboard'));
            return redirect()->to(base_url('login'));
        }

        return null;
    }

    private function isAdmin(): bool
    {
        return session()->get('role') === 'admin';
    }

    private function layoutFor(): array
    {
        return $this->isAdmin()
            ? ['layout' => 'layouts/AdminLayout',        'section' => 'adminContent']
            : ['layout' => 'layouts/ReceptionistLayout', 'section' => 'receptionistContent'];
    }

    private function routeBase(): string
    {
        return $this->isAdmin() ? 'admin/billing' : 'receptionist/billing';
    }

    private function viewData(array $data = []): array
    {
        return array_merge([
            'layout'      => $this->layoutFor()['layout'],
            'section'     => $this->layoutFor()['section'],
            'routeBase'   => $this->routeBase(),
            'isAdminUser' => $this->isAdmin(),
            'currentUser' => PaymentModel::currentCashierName(),
        ], $data);
    }

    // =============================================================
    // INDEX
    // =============================================================

    public function index()
    {
    $redirect = $this->checkAuth();
    if ($redirect) return $redirect;

    $invoiceModel = new InvoiceModel();

    // ── Safety pass ─────────────────────────────────────────────
    // Recalculate every non-terminal invoice whose stored
    // amount_paid disagrees with the live sum of its paid
    // payments. This is cheap (one query) and makes the list
    // self-healing even if a payment was inserted by a code
    // path that skipped the recalc step.
    try {
        $drifted = db_connect()->query("
            SELECT i.id
            FROM invoices i
            WHERE i.status NOT IN ('void', 'refunded')
              AND i.amount_paid <> (
                  SELECT COALESCE(SUM(p.amount_paid), 0)
                  FROM payments p
                  WHERE p.invoice_id = i.id
                    AND p.payment_status = 'paid'
              )
        ")->getResultArray();

        foreach ($drifted as $row) {
            $invoiceModel->recalculate((int) $row['id']);
        }
    } catch (\Throwable $e) {
        // Never block the list from rendering.
        log_message('warning', 'Billing::index safety recalc failed: ' . $e->getMessage());
    }

    $status  = (string) ($this->request->getGet('status') ?? 'all');
    $search  = trim((string) ($this->request->getGet('q') ?? ''));
    $from    = (string) ($this->request->getGet('from') ?? '');
    $to      = (string) ($this->request->getGet('to') ?? '');
    $page    = max(1, (int) ($this->request->getGet('page') ?? 1));
    $perPage = 15;

    $filters = [
        'status' => in_array($status, ['all','draft','unpaid','partial','paid','void','refunded'], true)
                    ? $status : 'all',
        'search' => $search,
        'from'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '',
        'to'     => preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ? $to   : '',
    ];

    $result = $invoiceModel->listInvoices($filters, $perPage, $page);
    $stats  = $invoiceModel->getStats();

    $counts = [
        'all'      => (int) $invoiceModel->countAllResults(),
        'draft'    => (int) $invoiceModel->where('status', 'draft')->countAllResults(),
        'unpaid'   => (int) $invoiceModel->where('status', 'unpaid')->countAllResults(),
        'partial'  => (int) $invoiceModel->where('status', 'partial')->countAllResults(),
        'paid'     => (int) $invoiceModel->where('status', 'paid')->countAllResults(),
        'void'     => (int) $invoiceModel->where('status', 'void')->countAllResults(),
        'refunded' => (int) $invoiceModel->where('status', 'refunded')->countAllResults(),
    ];

    return view('Billing/index', $this->viewData([
        'invoices'   => $result['rows'],
        'total'      => $result['total'],
        'page'       => $page,
        'perPage'    => $perPage,
        'totalPages' => max(1, (int) ceil($result['total'] / $perPage)),
        'filters'    => $filters,
        'stats'      => $stats,
        'counts'     => $counts,
    ]));
    }

    // =============================================================
    // CREATE
    // =============================================================

    public function create()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $serviceModel    = new ServiceModel();
        $diagnosticModel = new DiagnosticRequestModel();

        $requests = $diagnosticModel
            ->orderBy('created_at', 'DESC')
            ->findAll(200);

        $invoicedKeys = [];
        $invoiceModel = new InvoiceModel();
        foreach ($invoiceModel->select('request_id, request_type')
                              ->where('request_id IS NOT NULL')
                              ->findAll() as $row) {
            $invoicedKeys[$row['request_type'] . '-' . $row['request_id']] = true;
        }

        $openRequests = [];
        foreach ($requests as $r) {
            $key = ($r['type'] ?? '') . '-' . ($r['id'] ?? '');
            if (isset($invoicedKeys[$key])) {
                continue;
            }
            $openRequests[] = $r;
        }

        $services = $serviceModel
            ->where('is_active', 1)
            ->orderBy('category', 'ASC')
            ->orderBy('service_name', 'ASC')
            ->findAll();

        return view('Billing/create', $this->viewData([
            'openRequests' => $openRequests,
            'services'     => $services,
        ]));
    }

    public function store()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $post = $this->request->getPost();

        $patientName   = trim((string) ($post['patient_name'] ?? ''));
        $patientCode   = trim((string) ($post['patient_code'] ?? ''));
        $appointmentId = !empty($post['appointment_id']) ? (int) $post['appointment_id'] : null;
        $requestId     = !empty($post['request_id'])     ? (int) $post['request_id']     : null;
        $requestType   = in_array($post['request_type'] ?? '', ['lab', 'xray'], true)
                            ? $post['request_type'] : null;

        // Prevent duplicate invoices for the same diagnostic request.
        if ($requestId && $requestType) {
            $invoiceModel = new InvoiceModel();
            $existing = $invoiceModel
                ->where('request_id', $requestId)
                ->where('request_type', $requestType)
                ->first();

            if ($existing) {
                return redirect()->to(base_url($this->routeBase() . '/view/' . $existing['id']))
                    ->with('error', 'An invoice already exists for this request: ' . $existing['invoice_no']);
            }
        }

        if ($patientName === '') {
            return redirect()->back()->withInput()
                             ->with('error', 'Patient name is required.');
        }

        $rawItems = $post['items'] ?? [];
        if (!is_array($rawItems) || empty($rawItems)) {
            return redirect()->back()->withInput()
                             ->with('error', 'At least one line item is required.');
        }

        $items = [];
        foreach ($rawItems as $item) {
            $name = trim((string) ($item['service_name'] ?? ''));
            if ($name === '') continue;

            $qty   = (float) ($item['qty']   ?? 1);
            $price = (float) ($item['price'] ?? 0);
            if ($qty   <= 0) $qty   = 1;
            if ($price <  0) $price = 0;

            $items[] = [
                'service_name' => $name,
                'category'     => $item['category'] ?? null,
                'qty'          => $qty,
                'unit_price'   => $price,
                'notes'        => $item['notes'] ?? null,
            ];
        }

        if (empty($items)) {
            return redirect()->back()->withInput()
                             ->with('error', 'At least one valid line item is required.');
        }

        $discountType   = in_array($post['discount_type'] ?? '', ['none','percent','fixed'], true)
                            ? $post['discount_type'] : 'none';
        $discountValue  = (float) ($post['discount_value'] ?? 0);
        $discountReason = trim((string) ($post['discount_reason'] ?? ''));

        $invoiceModel = new InvoiceModel();
        $money        = $invoiceModel->computeTotals($items, $discountType, $discountValue, 0.0);

        $invoiceNo = $invoiceModel->generateInvoiceNo();

        $invoiceId = $invoiceModel->insert([
            'invoice_no'      => $invoiceNo,
            'patient_code'    => $patientCode !== '' ? $patientCode : null,
            'patient_name'    => $patientName,
            'appointment_id'  => $appointmentId,
            'request_id'      => $requestId,
            'request_type'    => $requestType,
            'subtotal'        => $money['subtotal'],
            'discount_type'   => $money['discount_type'],
            'discount_value'  => $money['discount_value'],
            'discount_reason' => $discountReason !== '' ? $discountReason : null,
            'tax'             => $money['tax'],
            'total'           => $money['total'],
            'amount_paid'     => '0.00',
            'balance'         => $money['total'],
            'status'          => InvoiceModel::STATUS_UNPAID,
            'notes'           => trim((string) ($post['notes'] ?? '')) ?: null,
            'created_by'      => PaymentModel::currentCashierName(),
        ]) ? (int) $invoiceModel->getInsertID() : 0;

        if (!$invoiceId) {
            return redirect()->back()->withInput()
                             ->with('error', 'Could not save the invoice. Please try again.');
        }

        (new InvoiceItemModel())->replaceForInvoice($invoiceId, $items);

        // ── SYNC BACK TO REQUEST ───────────────────────────────────
        // If this invoice is linked to a diagnostic request, push the
        // line items back to the request so both stay aligned. Only
        // applies when the request is still pending. Failures are
        // logged and swallowed — the invoice is what matters, the
        // sync is a courtesy.
        if ($requestId && $requestType) {
            try {
                $invoiceModel->syncToDiagnosticRequest($invoiceId, false);
            } catch (\Throwable $e) {
                log_message('error', 'Billing: sync to request failed for invoice #' . $invoiceId . ': ' . $e->getMessage());
            }
        }

        return redirect()->to(base_url($this->routeBase() . '/view/' . $invoiceId))
                         ->with('success', 'Invoice ' . $invoiceNo . ' created.');
    }

    // =============================================================
    // VIEW
    // =============================================================

    public function view($id = null)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $id = (int) $id;
        if ($id <= 0) {
            return redirect()->to(base_url($this->routeBase()))
                             ->with('error', 'Invalid invoice.');
        }

        $invoiceModel = new InvoiceModel();
        $itemModel    = new InvoiceItemModel();
        $paymentModel = new PaymentModel();

        $invoice = $invoiceModel->find($id);
        if (!$invoice) {
            return redirect()->to(base_url($this->routeBase()))
                             ->with('error', 'Invoice not found.');
        }

        $invoiceModel->recalculate($id);
        $invoice = $invoiceModel->find($id);

        $items    = $itemModel->getForInvoice($id);
        $payments = $paymentModel->getForInvoice($id);

        // Fetch the linked request if any, so the view can show its
        // current status (needed for the Sync button's enabled state).
        $request = null;
        if (!empty($invoice['request_id']) && !empty($invoice['request_type'])) {
            $request = db_connect()->table('diagnostic_requests')
                ->where('id', (int) $invoice['request_id'])
                ->where('type', $invoice['request_type'])
                ->get()
                ->getRowArray();
        }

        return view('Billing/view', $this->viewData([
            'invoice'  => $invoice,
            'items'    => $items,
            'payments' => $payments,
            'request'  => $request,
        ]));
    }

    // =============================================================
    // SYNC REQUEST (invoice -> diagnostic request)
    // =============================================================

    public function syncRequest($id = null)
    {
        $redirect = $this->checkAuth(['admin']);   // admin only
        if ($redirect) return $redirect;

        $id = (int) $id;
        $invoiceModel = new InvoiceModel();

        $ok = $invoiceModel->syncToDiagnosticRequest($id, false);

        if (!$ok) {
            return redirect()->to(base_url($this->routeBase() . '/view/' . $id))
                ->with('error', 'Could not sync. The request may already be in progress, or the invoice has no line items.');
        }

        return redirect()->to(base_url($this->routeBase() . '/view/' . $id))
            ->with('success', 'Diagnostic request updated to match the invoice.');
    }

    // =============================================================
    // RECORD PAYMENT
    // =============================================================

    public function recordPayment($id = null)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $id           = (int) $id;
        $invoiceModel = new InvoiceModel();
        $paymentModel = new PaymentModel();

        $invoice = $invoiceModel->find($id);
        if (!$invoice) {
            return redirect()->back()->with('error', 'Invoice not found.');
        }

        if (in_array($invoice['status'], [InvoiceModel::STATUS_VOID, InvoiceModel::STATUS_REFUNDED], true)) {
            return redirect()->back()->with('error', 'This invoice cannot receive payments.');
        }

        $post   = $this->request->getPost();
        $amount = round((float) ($post['amount'] ?? 0), 2);
        $method = in_array($post['method'] ?? '', ['cash','card','gcash','paymaya','insurance'], true)
                    ? $post['method'] : 'cash';
        $refNo = trim((string) ($post['reference_no'] ?? ''));
        $notes = trim((string) ($post['notes'] ?? ''));

        if ($amount <= 0) {
            return redirect()->back()->withInput()
                             ->with('error', 'Payment amount must be greater than zero.');
        }

        $itemModel = new InvoiceItemModel();
        $items     = $itemModel->getForInvoice($id);

        $paymentServices = [];
        foreach ($items as $it) {
            $paymentServices[] = [
                'name'   => $it['service_name'],
                'charge' => (float) $it['unit_price'],
            ];
        }

        if (empty($paymentServices)) {
            $paymentServices[] = [
                'name'   => 'Invoice ' . $invoice['invoice_no'],
                'charge' => (float) $invoice['total'],
            ];
        }

        $changeAmount = 0.0;
        if ($method === 'cash' && !empty($post['cash_tendered'])) {
            $tendered     = round((float) $post['cash_tendered'], 2);
            $changeAmount = max(0, $tendered - $amount);
        }

        $paymentId = $paymentModel->recordPayment([
            'invoice_id'     => $id,
            'request_id'     => $invoice['request_id'] ?? 0,
            'request_type'   => $invoice['request_type'] ?? 'lab',
            'patient_name'   => $invoice['patient_name'],
            'patient_code'   => $invoice['patient_code'],
            'appointment_id' => $invoice['appointment_id'],
            'services'       => json_encode($paymentServices),
            'total_amount'   => $invoice['total'],
            'amount_paid'    => $amount,
            'change_amount'  => $changeAmount,
            'payment_method' => $method,
            'reference_no'   => $refNo !== '' ? $refNo : null,
            'notes'          => $notes !== '' ? $notes : null,
        ]);

        if (!$paymentId) {
            return redirect()->back()->withInput()
                             ->with('error', 'Payment could not be recorded.');
        }

        $invoiceModel->recalculate($id);

        return redirect()->to(base_url($this->routeBase() . '/view/' . $id))
                         ->with('success', 'Payment recorded: ₱' . number_format($amount, 2));
    }

    // =============================================================
    // VOID
    // =============================================================

    public function void($id = null)
    {
        $redirect = $this->checkAuth(['admin']);
        if ($redirect) return $redirect;

        $id           = (int) $id;
        $invoiceModel = new InvoiceModel();

        $reason = trim((string) $this->request->getPost('reason'));
        if ($reason === '') {
            return redirect()->back()->with('error', 'A void reason is required.');
        }

        $ok = $invoiceModel->voidInvoice($id, $reason, PaymentModel::currentCashierName());

        if (!$ok) {
            return redirect()->back()->with('error', 'Invoice could not be voided.');
        }

        return redirect()->to(base_url($this->routeBase() . '/view/' . $id))
                         ->with('success', 'Invoice voided.');
    }

    // =============================================================
    // REFUND
    // =============================================================

    public function refundPayment($id = null)
    {
        $redirect = $this->checkAuth(['admin']);
        if ($redirect) return $redirect;

        $id           = (int) $id;
        $paymentModel = new PaymentModel();

        $payment = $paymentModel->find($id);
        if (!$payment) {
            return redirect()->back()->with('error', 'Payment not found.');
        }

        $reason = trim((string) $this->request->getPost('reason'));
        if ($reason === '') {
            $reason = 'Refunded by admin';
        }

        $ok = $paymentModel->voidPayment($id, $reason, PaymentModel::currentCashierName());

        if (!$ok) {
            return redirect()->back()->with('error', 'Payment could not be refunded.');
        }

        if (!empty($payment['invoice_id'])) {
            (new InvoiceModel())->recalculate((int) $payment['invoice_id']);
        }

        $to = !empty($payment['invoice_id'])
            ? base_url($this->routeBase() . '/view/' . $payment['invoice_id'])
            : base_url($this->routeBase());

        return redirect()->to($to)->with('success', 'Payment refunded.');
    }

    // =============================================================
    // RECEIPT
    // =============================================================

    public function receipt($paymentId = null)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $paymentId    = (int) $paymentId;
        $paymentModel = new PaymentModel();
        $payment      = $paymentModel->find($paymentId);

        if (!$payment) {
            return redirect()->to(base_url($this->routeBase()))
                             ->with('error', 'Receipt not found.');
        }

        $items   = [];
        $decoded = json_decode((string) ($payment['services'] ?? '[]'), true);
        if (is_array($decoded)) {
            foreach ($decoded as $s) {
                if (is_array($s)) {
                    $items[] = [
                        'name'  => (string) ($s['name'] ?? $s['service_name'] ?? ''),
                        'price' => isset($s['price']) ? (float) $s['price']
                                 : (isset($s['charge']) ? (float) $s['charge']
                                 : (isset($s['amount']) ? (float) $s['amount'] : null)),
                    ];
                } else {
                    $items[] = ['name' => (string) $s, 'price' => null];
                }
            }
        }

        $clinic = [
            'name'    => 'POLYMEDIC DIAGNOSTIC CENTER',
            'tagline' => 'Clinical Laboratory & Radiology Services',
            'address' => 'Quezon Avenue, Kidapawan City, Cotabato 9400',
            'phone'   => '(064) 123-4567',
            'email'   => 'info@polymedic.ph',
            'tin'     => '000-000-000-000',
        ];

        return view('Billing/receipt', [
            'payment' => $payment,
            'items'   => $items,
            'clinic'  => $clinic,
        ]);
    }

    // =============================================================
    // REPORTS
    // =============================================================

    public function reports()
    {
        $redirect = $this->checkAuth(['admin']);
        if ($redirect) return $redirect;

        $from = (string) ($this->request->getGet('from') ?? date('Y-m-01'));
        $to   = (string) ($this->request->getGet('to')   ?? date('Y-m-d'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

        $db = db_connect();

        $daily = $db->table('payments')
            ->select('DATE(payment_date) AS d, COUNT(*) AS n, SUM(amount_paid) AS collected')
            ->where('payment_status', PaymentModel::STATUS_PAID)
            ->where('payment_date >=', $from . ' 00:00:00')
            ->where('payment_date <=', $to   . ' 23:59:59')
            ->groupBy('d')
            ->orderBy('d', 'ASC')
            ->get()->getResultArray();

        $serviceRevenue = [];
        $rawPayments = $db->table('payments')
            ->select('services, amount_paid')
            ->where('payment_status', PaymentModel::STATUS_PAID)
            ->where('payment_date >=', $from . ' 00:00:00')
            ->where('payment_date <=', $to   . ' 23:59:59')
            ->get()->getResultArray();

        foreach ($rawPayments as $p) {
            $decoded = json_decode((string) ($p['services'] ?? '[]'), true);
            if (!is_array($decoded)) continue;
            foreach ($decoded as $s) {
                if (is_array($s)) {
                    $name   = trim((string) ($s['name'] ?? $s['service_name'] ?? ''));
                    $charge = (float) ($s['price'] ?? $s['charge'] ?? $s['amount'] ?? 0);
                } else {
                    $name   = trim((string) $s);
                    $charge = 0.0;
                }
                if ($name === '') continue;
                if (!isset($serviceRevenue[$name])) {
                    $serviceRevenue[$name] = ['count' => 0, 'revenue' => 0.0];
                }
                $serviceRevenue[$name]['count']++;
                $serviceRevenue[$name]['revenue'] += $charge;
            }
        }
        uasort($serviceRevenue, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        $outstanding = $db->table('invoices')
            ->selectSum('balance', 'total')
            ->whereIn('status', [InvoiceModel::STATUS_UNPAID, InvoiceModel::STATUS_PARTIAL])
            ->get()->getRow();

        $agingRaw = $db->table('invoices')
            ->select('created_at, balance')
            ->whereIn('status', [InvoiceModel::STATUS_UNPAID, InvoiceModel::STATUS_PARTIAL])
            ->get()->getResultArray();

        $aging = ['0-30' => 0.0, '31-60' => 0.0, '61+' => 0.0];
        $today = new \DateTime(date('Y-m-d'));
        foreach ($agingRaw as $row) {
            $days = (int) $today->diff(new \DateTime(date('Y-m-d', strtotime($row['created_at']))))->days;
            $bal  = (float) $row['balance'];
            if ($days <= 30)     $aging['0-30']  += $bal;
            elseif ($days <= 60) $aging['31-60'] += $bal;
            else                 $aging['61+']   += $bal;
        }

        return view('Billing/reports', $this->viewData([
            'from'           => $from,
            'to'             => $to,
            'daily'          => $daily,
            'serviceRevenue' => $serviceRevenue,
            'outstanding'    => (float) ($outstanding->total ?? 0),
            'aging'          => $aging,
        ]));
    }
}