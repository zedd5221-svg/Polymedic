<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'invoice_id',
        'request_id',
        'request_type',
        'patient_name',
        'patient_code',
        'appointment_id',
        'services',
        'total_amount',
        'amount_paid',
        'change_amount',
        'payment_method',
        'reference_no',
        'payment_status',
        'payment_date',
        'reference_number',
        'received_by',
        'received_by_id',
        'voided_by',
        'voided_at',
        'void_reason',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // ── Statuses (matches the DB enum) ───────────────────────────
    const STATUS_PAID     = 'paid';
    const STATUS_PENDING  = 'pending';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_FAILED   = 'failed';

    // ── Methods (matches the DB enum) ────────────────────────────
    const METHOD_CASH      = 'cash';
    const METHOD_CARD      = 'card';
    const METHOD_GCASH     = 'gcash';
    const METHOD_PAYMAYA   = 'paymaya';
    const METHOD_INSURANCE = 'insurance';

    // ============================================================
    // EXISTING HELPERS — unchanged
    // ============================================================

    /**
     * The signed-in staff member as [id, full name].
     *
     * The name is read from the users table (once per request) rather
     * than the session, so a rename by an admin is picked up straight
     * away instead of stamping the old name on new records.
     *
     * @return array{id: ?int, name: string}
     */
    public static function currentStaff(): array
    {
        static $cache = [];

        $session = session();
        $userId  = (int) ($session->get('user_id') ?? 0);

        if (isset($cache[$userId])) {
            return $cache[$userId];
        }

        $name = '';
        if ($userId > 0) {
            try {
                $row  = (new UserModel())->select('full_name')->find($userId);
                $name = trim((string) ($row['full_name'] ?? ''));
            } catch (\Throwable $e) {
                // fall back to the session below
            }
        }
        if ($name === '') {
            $name = trim((string) ($session->get('full_name') ?? ''))
                ?: trim((string) ($session->get('username') ?? ''))
                ?: 'Front desk';
        }

        return $cache[$userId] = ['id' => $userId > 0 ? $userId : null, 'name' => $name];
    }

    public static function currentCashierName(): string
    {
        return self::currentStaff()['name'];
    }

    public static function currentStaffId(): ?int
    {
        return self::currentStaff()['id'];
    }

    public function generateReference()
    {
        $prefix = 'PAY-';
        $year   = date('y');
        $month  = date('m');
        $day    = date('d');
        $random = strtoupper(substr(uniqid(), -4));

        return $prefix . $year . $month . $day . '-' . $random;
    }

    // ============================================================
    // recordPayment — INSERT + AUTO-LINK + AUTO-RECALC
    // ============================================================
    //
    // Returns the new row id on success, or 0 on failure.
    //
    // What this version does, in order:
    //   1. Auto-link to an existing invoice for the same
    //      (request_id, request_type) if the caller didn't supply
    //      an invoice_id. This prevents orphan payments from any
    //      code path that forgets to set it.
    //   2. Insert the payment row.
    //   3. Recalculate the linked invoice immediately, so paid /
    //      balance / status are correct before the next page load —
    //      regardless of who called this method.
    //
    // Every step is wrapped so a failure in linking or recalc never
    // blocks the payment row itself.
    // ============================================================

    public function recordPayment($data)
    {
        // Callers that know the cash tendered (Billing) pass the change
        // explicitly; otherwise derive it from an over-payment.
        if (isset($data['change_amount'])) {
            $changeAmount = (float) $data['change_amount'];
        } else {
            $changeAmount = 0;
            if (isset($data['amount_paid']) && isset($data['total_amount'])) {
                $changeAmount = $data['amount_paid'] - $data['total_amount'];
            }
        }

        $referenceNumber = $this->generateReference();

        $paymentData = [
            'invoice_id'       => isset($data['invoice_id']) ? (int) $data['invoice_id'] : null,
            'request_id'       => $data['request_id'],
            'request_type'     => $data['request_type'],
            'patient_name'     => $data['patient_name'],
            'patient_code'     => $data['patient_code'] ?? null,
            'appointment_id'   => $data['appointment_id'] ?? null,
            'services'         => $data['services'] ?? null,
            'total_amount'     => $data['total_amount'],
            'amount_paid'      => $data['amount_paid'] ?? $data['total_amount'],
            'change_amount'    => $changeAmount > 0 ? $changeAmount : 0,
            'payment_method'   => $data['payment_method'] ?? self::METHOD_CASH,
            'payment_status'   => self::STATUS_PAID,
            'payment_date'     => date('Y-m-d H:i:s'),
            'reference_number' => $referenceNumber,
            'reference_no'     => $data['reference_no'] ?? $referenceNumber,
            'received_by'      => $data['received_by'] ?? self::currentCashierName(),
            'received_by_id'   => $data['received_by_id'] ?? self::currentStaffId(),
            'notes'            => $data['notes'] ?? null,
        ];

        // ── STEP 1: auto-link to an existing invoice if possible ──
        if (empty($paymentData['invoice_id'])
            && !empty($paymentData['request_id'])
            && !empty($paymentData['request_type'])
            && in_array($paymentData['request_type'], ['lab', 'xray'], true)
        ) {
            try {
                $existing = $this->db->table('invoices')
                    ->select('id')
                    ->where('request_id', (int) $paymentData['request_id'])
                    ->where('request_type', (string) $paymentData['request_type'])
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getRowArray();

                if ($existing && !empty($existing['id'])) {
                    $paymentData['invoice_id'] = (int) $existing['id'];

                    log_message(
                        'info',
                        'PaymentModel: auto-linked new payment for request #'
                        . $paymentData['request_id']
                        . ' (' . $paymentData['request_type']
                        . ') to invoice #' . $paymentData['invoice_id']
                    );
                }
            } catch (\Throwable $e) {
                log_message('warning', 'PaymentModel: auto-link lookup failed: ' . $e->getMessage());
            }
        }

        // ── STEP 2: insert the payment row ────────────────────────
        try {
            $this->insert($paymentData);
            $id = (int) $this->getInsertID();

            if ($id <= 0) {
                $err = $this->db->error();
                log_message(
                    'error',
                    'PaymentModel::recordPayment — insert returned no id. DB error: '
                    . ($err['message'] ?? 'unknown')
                    . ' | Code: ' . ($err['code'] ?? 'n/a')
                );
                return 0;
            }
        } catch (\Throwable $e) {
            log_message('error', 'PaymentModel::recordPayment — exception: ' . $e->getMessage());
            return 0;
        }

        // ── STEP 3: recalculate the linked invoice immediately ────
        //
        // This is the permanent fix. No matter which caller inserted
        // the payment (Start button, manual record, sync hook,
        // backfill script), the invoice's amount_paid / balance /
        // status are updated before the request returns.
        if (!empty($paymentData['invoice_id'])) {
            try {
                (new InvoiceModel())->recalculate((int) $paymentData['invoice_id']);

                log_message(
                    'info',
                    'PaymentModel: invoice #' . $paymentData['invoice_id']
                    . ' recalculated after payment #' . $id
                );
            } catch (\Throwable $e) {
                // Never let the recalc failure hide the successful payment.
                // The next view of the invoice will fix it via
                // Billing::view()'s own recalculate call.
                log_message(
                    'error',
                    'PaymentModel: post-insert recalc failed for invoice #'
                    . $paymentData['invoice_id'] . ': ' . $e->getMessage()
                );
            }
        }

        return $id;
    }

    // ============================================================
    // EXISTING QUERY HELPERS — unchanged
    // ============================================================

    public function getByRequest($requestId, $requestType)
    {
        return $this->where('request_id', $requestId)
                    ->where('request_type', $requestType)
                    ->first();
    }

    public function getByPatient($patientName)
    {
        return $this->where('patient_name', $patientName)
                    ->orderBy('payment_date', 'DESC')
                    ->findAll();
    }

    public function getByDateRange($startDate, $endDate)
    {
        return $this->where('payment_date >=', $startDate . ' 00:00:00')
                    ->where('payment_date <=', $endDate . ' 23:59:59')
                    ->orderBy('payment_date', 'DESC')
                    ->findAll();
    }

    public function getTodayCollections()
    {
        $today = date('Y-m-d');
        return $this->where('DATE(payment_date)', $today)
                    ->where('payment_status', self::STATUS_PAID)
                    ->findAll();
    }

    public function getTodayTotal()
    {
        $today = date('Y-m-d');
        $result = $this->select('SUM(total_amount) as total')
                       ->where('DATE(payment_date)', $today)
                       ->where('payment_status', self::STATUS_PAID)
                       ->first();
        return $result['total'] ?? 0;
    }

    public function getMonthlyCollections($year, $month)
    {
        return $this->where('YEAR(payment_date)', $year)
                    ->where('MONTH(payment_date)', $month)
                    ->where('payment_status', self::STATUS_PAID)
                    ->orderBy('payment_date', 'DESC')
                    ->findAll();
    }

    public function getCounts()
    {
        return [
            'total'    => $this->countAll(),
            'paid'     => $this->where('payment_status', self::STATUS_PAID)->countAllResults(),
            'pending'  => $this->where('payment_status', self::STATUS_PENDING)->countAllResults(),
            'refunded' => $this->where('payment_status', self::STATUS_REFUNDED)->countAllResults(),
            'failed'   => $this->where('payment_status', self::STATUS_FAILED)->countAllResults(),
        ];
    }

    public function getXrayRevenueBetween(string $from, string $to): float
    {
        return $this->getRevenueBetween('xray', $from, $to);
    }

    /** Paid collections for one department (lab | xray) in a date window. */
    public function getRevenueBetween(string $type, string $from, string $to): float
    {
        $row = $this->selectSum('amount_paid', 'revenue')
                    ->where('request_type', $type)
                    ->where('payment_status', self::STATUS_PAID)
                    ->where('payment_date >=', $from . ' 00:00:00')
                    ->where('payment_date <=', $to   . ' 23:59:59')
                    ->first();

        return (float) ($row['revenue'] ?? 0);
    }

    public function getXrayRevenueToday(): float
    {
        return $this->getXrayRevenueBetween(date('Y-m-d'), date('Y-m-d'));
    }

    public function getXrayRevenueThisMonth(): float
    {
        return $this->getXrayRevenueBetween(date('Y-m-01'), date('Y-m-d'));
    }

    // ============================================================
    // NEW METHODS (invoice-aware)
    // ============================================================

    /**
     * All payments that belong to an invoice, oldest first.
     */
    public function getForInvoice(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)
                    ->orderBy('payment_date', 'ASC')
                    ->findAll();
    }

    /**
     * Sum of amount_paid for an invoice, restricted to successful rows.
     */
    public function getSumForInvoice(int $invoiceId): float
    {
        $row = $this->selectSum('amount_paid', 'total')
                    ->where('invoice_id', $invoiceId)
                    ->where('payment_status', self::STATUS_PAID)
                    ->first();

        return (float) ($row['total'] ?? 0);
    }

    /**
     * Void a payment (bookkeeping only — no money moves).
     * Automatically recalculates the linked invoice afterwards.
     */
    public function voidPayment(int $paymentId, string $reason, string $byName): bool
    {
        $payment = $this->find($paymentId);
        if (!$payment || $payment['payment_status'] === self::STATUS_REFUNDED) {
            return false;
        }

        $ok = (bool) $this->update($paymentId, [
            'payment_status' => self::STATUS_REFUNDED,
            'voided_by'      => $byName,
            'voided_at'      => date('Y-m-d H:i:s'),
            'void_reason'    => $reason,
            'notes'          => trim(($payment['notes'] ?? '') . ' | Voided: ' . $reason),
        ]);

        // Recalculate the affected invoice so its status/balance
        // reflect the refund right away.
        if ($ok && !empty($payment['invoice_id'])) {
            try {
                (new InvoiceModel())->recalculate((int) $payment['invoice_id']);
            } catch (\Throwable $e) {
                log_message(
                    'error',
                    'PaymentModel::voidPayment recalc failed for invoice #'
                    . $payment['invoice_id'] . ': ' . $e->getMessage()
                );
            }
        }

        return $ok;
    }
}