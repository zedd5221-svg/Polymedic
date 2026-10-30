<?php

namespace App\Controllers;

use App\Models\InvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\PaymentModel;
use App\Models\PatientModel;
use App\Models\DiagnosticRequestModel;
use App\Models\ServiceModel;
use App\Controllers\Concerns\BuildsRevenueReport;

class Billing extends BaseController
{
    use BuildsRevenueReport;

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
            if ($role === 'med_tech')     return redirect()->to(base_url('medtech/dashboard'));
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
            ? ['layout' => 'Layouts/AdminLayout',        'section' => 'adminContent']
            : ['layout' => 'Layouts/ReceptionistLayout', 'section' => 'receptionistContent'];
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

        $byStatus = array_map('intval', array_column(
            db_connect()->table('invoices')->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray(),
            'n',
            'status'
        ));
        $counts = ['all' => array_sum($byStatus)];
        foreach (['draft', 'unpaid', 'partial', 'paid', 'void', 'refunded'] as $st) {
            $counts[$st] = $byStatus[$st] ?? 0;
        }

        // ── KPI TRENDS FOR SPARKLINES ──────────────────────────────
        // 14-day daily series feeding the mini line graphs on the
        // four KPI stat cards in the index view. If a metric has no
        // natural daily series (outstanding, unsettled), we reuse
        // the closest proxy so the graph still trends with activity.
        $kpiTrends = $this->getKpiTrends(14);

        return view('Billing/index', $this->viewData([
            'invoices'   => $result['rows'],
            'total'      => $result['total'],
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => max(1, (int) ceil($result['total'] / $perPage)),
            'filters'    => $filters,
            'stats'      => $stats,
            'counts'     => $counts,
            'kpiTrends'  => $kpiTrends,
        ]));
    }

    /**
     * Build a 14-day daily series for each KPI card.
     *
     * Returned shape:
     *   [
     *     'today'       => [float, float, ... 14 values],
     *     'month'       => [float, ... 14 values],
     *     'outstanding' => [float, ... 14 values],
     *     'unsettled'   => [float, ... 14 values],
     *   ]
     *
     * All queries are wrapped so a missing column or empty table
     * can never break the billing list — a flat-zero series is
     * returned instead, and the view falls back to its own
     * derived curve when fewer than 2 points are present.
     */
    private function getKpiTrends(int $days = 14): array
    {
        $db    = db_connect();
        $end   = date('Y-m-d');
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));

        // Build the exact date axis the view expects.
        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = date('Y-m-d', strtotime($start . ' +' . $i . ' days'));
        }

        /**
         * Run one grouped query and align its result onto $dates,
         * filling missing days with 0. Values are cast to float.
         */
        $series = function (string $sql, array $bind) use ($db, $dates): array {
            try {
                $rows = $db->query($sql, $bind)->getResultArray();
                $map  = [];
                foreach ($rows as $r) {
                    $map[$r['d']] = (float) ($r['v'] ?? 0);
                }
                return array_map(fn($d) => $map[$d] ?? 0.0, $dates);
            } catch (\Throwable $e) {
                log_message('warning', 'Billing::getKpiTrends query failed: ' . $e->getMessage());
                return array_fill(0, count($dates), 0.0);
            }
        };

        // ── Collected per day (today card) ────────────────────────
        $today = $series("
            SELECT DATE(payment_date) AS d, SUM(amount_paid) AS v
            FROM payments
            WHERE payment_status = 'paid'
              AND DATE(payment_date) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        // ── Cumulative month-to-date collected (month card) ───────
        // Sum from the first of the month up to each day, so the
        // line always reads as "how much we've collected so far".
        $monthStart = date('Y-m-01');
        $monthDaily = $series("
            SELECT DATE(payment_date) AS d, SUM(amount_paid) AS v
            FROM payments
            WHERE payment_status = 'paid'
              AND DATE(payment_date) BETWEEN ? AND ?
            GROUP BY d
        ", [$monthStart, $end]);

        // Convert daily → cumulative for the visible window.
        // Days before the 1st contribute 0 to the running sum.
        $monthMap = [];
        foreach ($dates as $i => $d) {
            $monthMap[$d] = $monthDaily[$i] ?? 0.0;
        }
        $monthCumulative = [];
        $running = 0.0;
        $cursor  = $monthStart;
        $sumLookup = [];
        while ($cursor <= $end) {
            $running += $monthMap[$cursor] ?? 0.0;
            $sumLookup[$cursor] = $running;
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }
        // Walk back over the visible 14-day window, carrying the
        // running total that applies to each of those days.
        $month = [];
        foreach ($dates as $d) {
            $month[] = $sumLookup[$d] ?? 0.0;
        }

        // ── Outstanding balance trend (outstanding card) ──────────
        // Running balance of open invoices created up to each day.
        // Downward is "good" for this metric.
        $outstandingPerDay = $series("
            SELECT DATE(created_at) AS d, SUM(balance) AS v
            FROM invoices
            WHERE status IN ('unpaid', 'partial')
              AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        // ── Unsettled invoice count (unsettled card) ──────────────
        $unsettledPerDay = $series("
            SELECT DATE(created_at) AS d, COUNT(*) AS v
            FROM invoices
            WHERE status IN ('unpaid', 'partial')
              AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY d
        ", [$start, $end]);

        return [
            'today'       => $today,
            'month'       => $month,
            'outstanding' => $outstandingPerDay,
            'unsettled'   => $unsettledPerDay,
        ];
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
            'created_by_id'   => PaymentModel::currentStaffId(),
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

        // Bring the stored balance up to date before comparing against it.
        $invoice = $invoiceModel->recalculate($id) ?? $invoice;
        $balance = round((float) $invoice['balance'], 2);
        if ($amount > $balance + 0.005) {
            return redirect()->back()->withInput()
                             ->with('error', 'Payment of ₱' . number_format($amount, 2)
                                 . ' is more than the remaining balance of ₱' . number_format($balance, 2)
                                 . '. Enter the cash tendered separately to compute change.');
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

        $clinic = config('Clinic')->toArray();

        return view('Billing/receipt', [
            'payment'      => $payment,
            'items'        => $items,
            'clinic'       => $clinic,
            'patientEmail' => $this->receiptEmail($payment),
        ]);
    }

    /** The patient's email for "Email PDF": from the paid request, else the patient record. */
    private function receiptEmail(array $payment): string
    {
        $db = db_connect();

        if (!empty($payment['request_id'])) {
            $row = $db->table('diagnostic_requests')->select('email')->where('id', (int) $payment['request_id'])->get()->getRow();
            if (!empty($row->email)) {
                return (string) $row->email;
            }
        }

        $code = trim((string) ($payment['patient_code'] ?? ''));
        if ($code !== '' && strtoupper($code) !== 'N/A') {
            $row = $db->table('patients')->select('email')->where('patient_code', $code)->get()->getRow();
            if (!empty($row->email)) {
                return (string) $row->email;
            }
        }

        return '';
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

    // =============================================================
    // REVENUE REPORT (admin) — printable A4, same paper as the
    // receptionist's, clinic-wide. GET: range, from, to, cashier, annex=0
    // =============================================================
    public function revenueReport()
    {
        $redirect = $this->checkAuth(['admin']);
        if ($redirect) return $redirect;

        return view('Reports/revenue', $this->revenueReportData(base_url('admin/billing/reports'), 'Administrator', 'month'));
    }
}