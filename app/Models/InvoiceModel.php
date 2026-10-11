<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceModel extends Model
{
    protected $table      = 'invoices';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'invoice_no',
        'patient_code',
        'patient_name',
        'appointment_id',
        'request_id',
        'request_type',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_reason',
        'tax',
        'total',
        'amount_paid',
        'balance',
        'status',
        'notes',
        'created_by',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    const STATUS_DRAFT    = 'draft';
    const STATUS_UNPAID   = 'unpaid';
    const STATUS_PARTIAL  = 'partial';
    const STATUS_PAID     = 'paid';
    const STATUS_VOID     = 'void';
    const STATUS_REFUNDED = 'refunded';

    // -------------------------------------------------------------
    // MONEY MATH
    // -------------------------------------------------------------

    public function computeTotals(array $items, string $discountType, float $discountValue, float $tax = 0.0): array
    {
        $subtotal = 0.0;
        foreach ($items as $item) {
            $qty   = (float) ($item['qty'] ?? 1);
            $price = (float) ($item['unit_price'] ?? $item['charge'] ?? 0);
            $subtotal += max(0, $qty) * max(0, $price);
        }
        $subtotal = round($subtotal, 2);

        $discountAmount = 0.0;
        switch ($discountType) {
            case 'percent':
                $pct = max(0, min(100, $discountValue));
                $discountAmount = round($subtotal * ($pct / 100), 2);
                break;
            case 'fixed':
                $discountAmount = round(min($subtotal, max(0, $discountValue)), 2);
                break;
            case 'none':
            default:
                $discountAmount = 0.0;
                $discountType   = 'none';
                break;
        }

        $tax      = round(max(0, $tax), 2);
        $total    = round(max(0, $subtotal - $discountAmount + $tax), 2);

        return [
            'subtotal'        => number_format($subtotal, 2, '.', ''),
            'discount_type'   => $discountType,
            'discount_value'  => number_format($discountValue, 2, '.', ''),
            'tax'             => number_format($tax, 2, '.', ''),
            'total'           => number_format($total, 2, '.', ''),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
        ];
    }

    public function recalculate(int $invoiceId): ?array
    {
        $invoice = $this->find($invoiceId);
        if (!$invoice) return null;

        if (in_array($invoice['status'], [self::STATUS_VOID, self::STATUS_REFUNDED], true)) {
            return $invoice;
        }

        $paid = (float) $this->db->table('payments')
            ->selectSum('amount_paid', 'paid')
            ->where('invoice_id', $invoiceId)
            ->where('payment_status', PaymentModel::STATUS_PAID)
            ->get()
            ->getRow()
            ->paid ?? 0;

        $paid    = round($paid, 2);
        $total   = (float) $invoice['total'];
        $balance = round(max(0, $total - $paid), 2);

        $status = self::STATUS_UNPAID;
        if ($paid > 0 && $paid < $total) {
            $status = self::STATUS_PARTIAL;
        } elseif ($paid >= $total && $total > 0) {
            $status = self::STATUS_PAID;
        } elseif ($total == 0 && $paid == 0) {
            $status = self::STATUS_DRAFT;
        }

        $this->update($invoiceId, [
            'amount_paid' => number_format($paid, 2, '.', ''),
            'balance'     => number_format($balance, 2, '.', ''),
            'status'      => $status,
        ]);

        return $this->find($invoiceId);
    }

    // -------------------------------------------------------------
    // NUMBERING
    // -------------------------------------------------------------

    public function generateInvoiceNo(): string
    {
        $year   = (int) date('Y');
        $prefix = 'INV-' . $year . '-';

        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING(invoice_no, ?) AS UNSIGNED)) AS max_seq
             FROM invoices
             WHERE invoice_no LIKE ?",
            [strlen($prefix) + 1, $prefix . '%']
        )->getRow();

        $next = ((int) ($row->max_seq ?? 0)) + 1;

        for ($i = 0; $i < 20; $i++) {
            $candidate = sprintf('INV-%d-%06d', $year, $next + $i);
            $exists = $this->where('invoice_no', $candidate)->countAllResults();
            if (!$exists) {
                return $candidate;
            }
        }

        return sprintf('INV-%d-%06d', $year, time() % 1000000);
    }

    // -------------------------------------------------------------
    // SYNC FROM DIAGNOSTIC PAYMENT (payment -> invoice)
    // -------------------------------------------------------------

    public function syncFromDiagnosticPayment(int $paymentId): ?int
    {
        $payment = $this->db->table('payments')
            ->where('id', $paymentId)
            ->get()
            ->getRowArray();

        if (!$payment) {
            return null;
        }

        if (!empty($payment['invoice_id'])) {
            $this->recalculate((int) $payment['invoice_id']);
            return (int) $payment['invoice_id'];
        }

        $existing = null;
        if (!empty($payment['request_id']) && !empty($payment['request_type'])) {
            $existing = $this->where('request_id', (int) $payment['request_id'])
                             ->where('request_type', (string) $payment['request_type'])
                             ->first();
        }

        if ($existing) {
            $this->db->table('payments')
                ->where('id', $paymentId)
                ->update([
                    'invoice_id'   => (int) $existing['id'],
                    'reference_no' => $payment['reference_number'] ?? null,
                ]);
            $this->recalculate((int) $existing['id']);
            return (int) $existing['id'];
        }

        $total = (float) ($payment['total_amount'] ?? 0);
        $paid  = (float) ($payment['amount_paid']  ?? $total);

        $invoiceNo = $this->generateInvoiceNo();

        $status = self::STATUS_PAID;
        if ($paid <= 0)         $status = self::STATUS_UNPAID;
        elseif ($paid < $total) $status = self::STATUS_PARTIAL;

        $invoiceId = $this->insert([
            'invoice_no'      => $invoiceNo,
            'patient_code'    => $payment['patient_code'] ?? null,
            'patient_name'    => (string) ($payment['patient_name'] ?? 'Unknown'),
            'appointment_id'  => $payment['appointment_id'] ?? null,
            'request_id'      => $payment['request_id'] ?? null,
            'request_type'    => in_array($payment['request_type'] ?? '', ['lab', 'xray'], true)
                                    ? $payment['request_type']
                                    : null,
            'subtotal'        => number_format($total, 2, '.', ''),
            'discount_type'   => 'none',
            'discount_value'  => '0.00',
            'discount_reason' => null,
            'tax'             => '0.00',
            'total'           => number_format($total, 2, '.', ''),
            'amount_paid'     => number_format($paid, 2, '.', ''),
            'balance'         => number_format(max(0, $total - $paid), 2, '.', ''),
            'status'          => $status,
            'notes'           => null,
            'created_by'      => $payment['received_by'] ?? PaymentModel::currentCashierName(),
        ]) ? (int) $this->getInsertID() : 0;

        if (!$invoiceId) {
            log_message('error', 'InvoiceModel::syncFromDiagnosticPayment — invoice insert failed for payment #' . $paymentId);
            return null;
        }

        $items = json_decode((string) ($payment['services'] ?? '[]'), true);
        if (!is_array($items)) {
            $items = [];
        }

        $itemModel = new InvoiceItemModel();
        $normalised = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $normalised[] = [
                    'service_name' => $item['name'] ?? $item['service_name'] ?? '',
                    'category'     => $item['category'] ?? null,
                    'qty'          => 1,
                    'unit_price'   => $item['price'] ?? $item['charge'] ?? $item['amount'] ?? 0,
                    'notes'        => null,
                ];
            } else {
                $normalised[] = [
                    'service_name' => (string) $item,
                    'category'     => null,
                    'qty'          => 1,
                    'unit_price'   => 0,
                    'notes'        => null,
                ];
            }
        }

        if (empty($normalised) && $total > 0) {
            $normalised[] = [
                'service_name' => 'Diagnostic services',
                'category'     => null,
                'qty'          => 1,
                'unit_price'   => $total,
                'notes'        => null,
            ];
        }

        $itemModel->replaceForInvoice($invoiceId, $normalised);

        $this->db->table('payments')
            ->where('id', $paymentId)
            ->update([
                'invoice_id'   => $invoiceId,
                'reference_no' => $payment['reference_number'] ?? null,
            ]);

        $this->recalculate($invoiceId);

        return $invoiceId;
    }

    // -------------------------------------------------------------
    // SYNC TO DIAGNOSTIC REQUEST (invoice -> request)
    // -------------------------------------------------------------
    // Writes the invoice's line items back to diagnostic_requests.services.
    //
    // Rules:
    //   • Only applies when the invoice is linked to a request.
    //   • Only applies when the request is still 'pending' — once the
    //     lab has started work, changing what they must do is unsafe.
    //     The admin can still add services manually on the request page.
    //   • Preserves services already on the request that aren't on the
    //     invoice (union, not replacement), unless $replace is true.
    //
    // Returns true on success, false on refusal, and logs why.
    // -------------------------------------------------------------

    public function syncToDiagnosticRequest(int $invoiceId, bool $replace = false): bool
    {
        $invoice = $this->find($invoiceId);
        if (!$invoice) {
            log_message('warning', 'syncToDiagnosticRequest: invoice #' . $invoiceId . ' not found.');
            return false;
        }

        $requestId   = (int) ($invoice['request_id'] ?? 0);
        $requestType = (string) ($invoice['request_type'] ?? '');

        if ($requestId <= 0 || !in_array($requestType, ['lab', 'xray'], true)) {
            log_message('info', 'syncToDiagnosticRequest: invoice #' . $invoiceId . ' has no linked diagnostic request.');
            return false;
        }

        $db = db_connect();

        $request = $db->table('diagnostic_requests')
            ->where('id', $requestId)
            ->where('type', $requestType)
            ->get()
            ->getRowArray();

        if (!$request) {
            log_message('warning', 'syncToDiagnosticRequest: request #' . $requestId . ' (' . $requestType . ') not found.');
            return false;
        }

        // Only allow writes when the request is still pending. Once it
        // is in progress, the lab may already be working on it.
        if (($request['status'] ?? 'pending') !== 'pending') {
            log_message(
                'info',
                'syncToDiagnosticRequest: request #' . $requestId
                . ' is ' . $request['status'] . '; refusal (only pending requests can be rewritten).'
            );
            return false;
        }

        $itemModel = new InvoiceItemModel();
        $items     = $itemModel->getForInvoice($invoiceId);

        if (empty($items)) {
            log_message('info', 'syncToDiagnosticRequest: invoice #' . $invoiceId . ' has no line items.');
            return false;
        }

        $invoiceNames = [];
        foreach ($items as $it) {
            $name = trim((string) ($it['service_name'] ?? ''));
            if ($name !== '') {
                $invoiceNames[$name] = true;
            }
        }

        // Existing services on the request (comma separated).
        $existingRaw = (string) ($request['services'] ?? '');
        $existing = array_filter(array_map('trim', explode(',', $existingRaw)), 'strlen');

        if ($replace) {
            $final = array_keys($invoiceNames);
        } else {
            // Union: keep everything, add the invoice names that
            // aren't already there.
            $final = $existing;
            foreach (array_keys($invoiceNames) as $name) {
                if (!in_array($name, $final, true)) {
                    $final[] = $name;
                }
            }
        }

        $final = array_values(array_unique(array_filter($final, 'strlen')));
        $joined = implode(', ', $final);

        if ($joined === $existingRaw) {
            log_message('info', 'syncToDiagnosticRequest: request #' . $requestId . ' already matches the invoice.');
            return true;
        }

        $db->table('diagnostic_requests')
            ->where('id', $requestId)
            ->where('type', $requestType)
            ->update([
                'services'   => $joined,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        log_message(
            'info',
            'syncToDiagnosticRequest: request #' . $requestId . ' (' . $requestType
            . ') services updated to: ' . $joined
        );

        return true;
    }

    // -------------------------------------------------------------
    // LISTING / FILTERING
    // -------------------------------------------------------------

    public function listInvoices(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        $builder = $this->builder();

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $builder->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $builder->groupStart()
                    ->like('invoice_no', $s)
                    ->orLike('patient_name', $s)
                    ->orLike('patient_code', $s)
                    ->groupEnd();
        }

        if (!empty($filters['from'])) {
            $builder->where('DATE(created_at) >=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $builder->where('DATE(created_at) <=', $filters['to']);
        }

        $countBuilder = clone $builder;
        $total = $countBuilder->countAllResults(false);

        $offset = max(0, ($page - 1) * $perPage);

        $rows = $builder->orderBy('created_at', 'DESC')
                        ->orderBy('id', 'DESC')
                        ->limit($perPage, $offset)
                        ->get()
                        ->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }

    /**
 * Headline numbers for the stats strip at the top of the billing list.
 *
 * These numbers now come from the payments table, filtered by
 * payment_date — the same source the Payments page uses. That way
 * the two dashboards always agree on "money collected today" and
 * "money collected this month".
 *
 * The Outstanding number still comes from invoices, because that
 * is genuinely about unpaid balances, not collections.
 */
public function getStats(): array
   {
    $today = date('Y-m-d');
    $month = date('m');
    $year  = date('Y');

    $db = $this->db;

    // -------- Collected today (from payments) --------
    $todayRow = $db->table('payments')
        ->selectSum('amount_paid', 'collected')
        ->where('DATE(payment_date)', $today)
        ->where('payment_status', PaymentModel::STATUS_PAID)
        ->get()
        ->getRow();

    $todayCollected = (float) ($todayRow->collected ?? 0);

    // -------- Collected this month (from payments) --------
    $monthRow = $db->table('payments')
        ->selectSum('amount_paid', 'collected')
        ->where('MONTH(payment_date)', $month)
        ->where('YEAR(payment_date)',  $year)
        ->where('payment_status', PaymentModel::STATUS_PAID)
        ->get()
        ->getRow();

    $monthCollected = (float) ($monthRow->collected ?? 0);

    // -------- Outstanding (from invoices — this one is correct) --------
    $outstandingRow = $db->table('invoices')
        ->selectSum('balance', 'outstanding')
        ->whereIn('status', [self::STATUS_UNPAID, self::STATUS_PARTIAL])
        ->get()
        ->getRow();

    $outstanding = (float) ($outstandingRow->outstanding ?? 0);

    // -------- Unsettled invoice count --------
    $unsettled = (int) $this->whereIn('status', [self::STATUS_UNPAID, self::STATUS_PARTIAL])
                            ->countAllResults();

    return [
        'today_collected' => $todayCollected,
        'month_collected' => $monthCollected,
        'outstanding'     => $outstanding,
        'unpaid_count'    => $unsettled,
        'total_count'     => (int) $this->countAllResults(),
    ];
    }
}