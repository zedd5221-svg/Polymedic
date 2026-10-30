<?php

namespace App\Controllers\Concerns;

use App\Models\InvoiceModel;
use App\Models\PaymentModel;

/**
 * Revenue figures shared by the receptionist Reports page and the
 * printable revenue report (receptionist and admin).
 *
 * Money basis: payments with status paid or refunded, by payment_date.
 * A refunded receipt stays in gross and is offset in full by the refund,
 * so net = paid receipts only (matches the Payments page).
 */
trait BuildsRevenueReport
{
    use ResolvesReportPeriod;

    /**
     * Everything the printable revenue report (Reports/revenue) needs.
     * GET: range, from, to, cashier, annex=0
     */
    private function revenueReportData(string $backUrl, string $preparedRole, string $defaultRange = 'today'): array
    {
        [$range, $from, $to] = $this->reportRange($defaultRange);
        $cashier = trim((string) ($this->request->getGet('cashier') ?? ''));

        [$days, $prevFrom, $prevTo] = $this->previousWindow($from, $to);
        $ytdFrom = date('Y-01-01', strtotime($to));

        $payments = $this->reportPayments($from, $to, $cashier);
        $query    = http_build_query(array_filter(['range' => $range, 'from' => $from, 'to' => $to, 'cashier' => $cashier], fn($v) => $v !== ''));

        return [
            'from'        => $from,
            'to'          => $to,
            'days'        => $days,
            'prevFrom'    => $prevFrom,
            'prevTo'      => $prevTo,
            'ytdFrom'     => $ytdFrom,
            'periodLabel' => self::periodLabel($from, $to),
            'prevLabel'   => self::periodLabel($prevFrom, $prevTo),
            'ytdLabel'    => self::periodLabel($ytdFrom, $to),
            'cashier'     => $cashier,
            'payments'    => $payments,
            'summary'     => $this->summarisePayments($payments),
            'prev'        => $this->summarisePayments($this->reportPayments($prevFrom, $prevTo, $cashier)),
            'departments' => [
                'current' => $this->departmentTotals($from, $to, $cashier),
                'prev'    => $this->departmentTotals($prevFrom, $prevTo, $cashier),
                'ytd'     => $this->departmentTotals($ytdFrom, $to, $cashier),
            ],
            'schedule'    => $this->revenueSchedule($payments, $from, $to),
            'unpaid'      => $this->unpaidInvoices(),
            'clinic'      => config('Clinic')->toArray(),
            'preparedBy'  => PaymentModel::currentCashierName(),
            'preparedRole'=> $preparedRole,
            'reportNo'    => 'RR-' . date('ymd', strtotime($from)) . ($from !== $to ? '-' . date('ymd', strtotime($to)) : ''),
            'annex'       => $this->request->getGet('annex') !== '0',
            'backUrl'     => $backUrl . ($query !== '' ? '?' . $query : ''),
        ];
    }

    /** Paid + refunded payments in the window, newest first. */
    private function reportPayments(string $from, string $to, string $cashier = ''): array
    {
        $builder = (new PaymentModel())
            ->whereIn('payment_status', [PaymentModel::STATUS_PAID, PaymentModel::STATUS_REFUNDED])
            ->where('payment_date >=', $from . ' 00:00:00')
            ->where('payment_date <=', $to . ' 23:59:59');

        if ($cashier !== '') {
            $builder->where('received_by', $cashier);
        }

        return $builder->orderBy('payment_date', 'DESC')->findAll();
    }

    /** Totals and breakdowns for a set of payment rows. */
    private function summarisePayments(array $payments): array
    {
        $s = [
            'gross' => 0.0, 'refunds' => 0.0, 'net' => 0.0,
            'count' => 0, 'refund_count' => 0, 'patients' => 0, 'avg' => 0.0,
            'cash' => 0.0, 'noncash' => 0.0, 'change' => 0.0,
            'methods' => [], 'types' => [], 'cashiers' => [], 'services' => [],
        ];
        $patients = [];

        foreach ($payments as $p) {
            $amount = (float) ($p['amount_paid'] ?? 0);
            $s['gross'] += $amount;

            if (($p['payment_status'] ?? '') === PaymentModel::STATUS_REFUNDED) {
                $s['refunds'] += $amount;
                $s['refund_count']++;
                continue;
            }

            $s['count']++;
            $s['change'] += (float) ($p['change_amount'] ?? 0);

            $method = strtolower((string) ($p['payment_method'] ?? '')) ?: 'other';
            $s['methods'][$method]['count']  = ($s['methods'][$method]['count'] ?? 0) + 1;
            $s['methods'][$method]['amount'] = ($s['methods'][$method]['amount'] ?? 0) + $amount;
            if ($method === PaymentModel::METHOD_CASH) {
                $s['cash'] += $amount;
            } else {
                $s['noncash'] += $amount;
            }

            $type = strtolower((string) ($p['request_type'] ?? '')) ?: 'other';
            $s['types'][$type]['count']  = ($s['types'][$type]['count'] ?? 0) + 1;
            $s['types'][$type]['amount'] = ($s['types'][$type]['amount'] ?? 0) + $amount;

            $who = trim((string) ($p['received_by'] ?? '')) ?: 'Unassigned';
            $s['cashiers'][$who]['count']  = ($s['cashiers'][$who]['count'] ?? 0) + 1;
            $s['cashiers'][$who]['amount'] = ($s['cashiers'][$who]['amount'] ?? 0) + $amount;

            $key = trim((string) ($p['patient_code'] ?? ''));
            if ($key === '' || strtoupper($key) === 'N/A') {
                $key = strtolower(trim((string) ($p['patient_name'] ?? '')));
            }
            if ($key !== '') $patients[$key] = true;

            // Line items are billed charges, not money received
            // (a partial payment still lists every service).
            $decoded = json_decode((string) ($p['services'] ?? '[]'), true);
            foreach (is_array($decoded) ? $decoded : [] as $item) {
                $name   = trim((string) (is_array($item) ? ($item['name'] ?? $item['service_name'] ?? '') : $item));
                $charge = is_array($item) ? (float) ($item['price'] ?? $item['charge'] ?? $item['amount'] ?? 0) : 0.0;
                if ($name === '') continue;
                $s['services'][$name]['count']  = ($s['services'][$name]['count'] ?? 0) + 1;
                $s['services'][$name]['charge'] = ($s['services'][$name]['charge'] ?? 0) + $charge;
                $s['services'][$name]['type']   = $type;
            }
        }

        $s['net']      = $s['gross'] - $s['refunds'];
        $s['patients'] = count($patients);
        $s['avg']      = $s['count'] > 0 ? $s['net'] / $s['count'] : 0.0;

        uasort($s['methods'], fn($a, $b) => $b['amount'] <=> $a['amount']);
        uasort($s['cashiers'], fn($a, $b) => $b['amount'] <=> $a['amount']);
        uasort($s['services'], fn($a, $b) => [$b['count'], $b['charge']] <=> [$a['count'], $a['charge']]);

        return $s;
    }

    /**
     * Gross / refunds / net and transaction count per department,
     * aggregated in SQL (same filters as reportPayments()).
     */
    private function departmentTotals(string $from, string $to, string $cashier = ''): array
    {
        $builder = db_connect()->table('payments')
            ->select('LOWER(request_type) AS type, payment_status, SUM(amount_paid) AS amount, COUNT(*) AS n', false)
            ->whereIn('payment_status', [PaymentModel::STATUS_PAID, PaymentModel::STATUS_REFUNDED])
            ->where('payment_date >=', $from . ' 00:00:00')
            ->where('payment_date <=', $to . ' 23:59:59')
            ->groupBy(['LOWER(request_type)', 'payment_status'], false);

        if ($cashier !== '') {
            $builder->where('received_by', $cashier);
        }

        $out = [];
        foreach (['lab', 'xray', 'other', 'total'] as $t) {
            $out[$t] = ['gross' => 0.0, 'refunds' => 0.0, 'net' => 0.0, 'count' => 0];
        }

        foreach ($builder->get()->getResultArray() as $r) {
            $type = in_array($r['type'], ['lab', 'xray'], true) ? $r['type'] : 'other';
            foreach ([$type, 'total'] as $t) {
                $out[$t]['gross'] += (float) $r['amount'];
                if ($r['payment_status'] === PaymentModel::STATUS_REFUNDED) {
                    $out[$t]['refunds'] += (float) $r['amount'];
                } else {
                    $out[$t]['count'] += (int) $r['n'];
                }
            }
        }
        foreach ($out as &$t) {
            $t['net'] = $t['gross'] - $t['refunds'];
        }
        unset($t);

        return $out;
    }

    /**
     * Revenue by period with department columns: hourly for one day,
     * daily up to two months, monthly beyond that.
     */
    private function revenueSchedule(array $payments, string $from, string $to): array
    {
        $days = (int) ((strtotime($to) - strtotime($from)) / 86400) + 1;
        $unit = $from === $to ? 'hour' : ($days > 62 ? 'month' : 'day');
        $blank = ['lab' => 0.0, 'xray' => 0.0, 'other' => 0.0, 'refunds' => 0.0, 'net' => 0.0, 'count' => 0];
        $rows  = [];

        if ($unit === 'hour') {
            for ($h = 7; $h <= 19; $h++) {
                $rows[sprintf('%02d', $h)] = ['label' => date('g:00 A', mktime($h, 0, 0))] + $blank;
            }
        } elseif ($unit === 'day') {
            for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
                $rows[$d] = ['label' => date('D, M j', strtotime($d))] + $blank;
            }
        } else {
            for ($m = date('Y-m-01', strtotime($from)); $m <= $to; $m = date('Y-m-01', strtotime($m . ' +1 month'))) {
                $rows[substr($m, 0, 7)] = ['label' => date('F Y', strtotime($m))] + $blank;
            }
        }

        foreach ($payments as $p) {
            $ts  = strtotime((string) $p['payment_date']);
            $key = ['hour' => date('H', $ts), 'day' => date('Y-m-d', $ts), 'month' => date('Y-m', $ts)][$unit];
            if (!isset($rows[$key])) {
                // Out-of-hours payment: add a row rather than drop it.
                $rows[$key] = ['label' => date('g:00 A', $ts)] + $blank;
            }

            $amount = (float) ($p['amount_paid'] ?? 0);
            // A refunded receipt stays in gross and is offset by the refund,
            // so it adds nothing to net (same as summarisePayments()).
            if (($p['payment_status'] ?? '') === PaymentModel::STATUS_REFUNDED) {
                $rows[$key]['refunds'] += $amount;
                continue;
            }

            $type = strtolower((string) ($p['request_type'] ?? ''));
            $rows[$key][in_array($type, ['lab', 'xray'], true) ? $type : 'other'] += $amount;
            $rows[$key]['net']   += $amount;
            $rows[$key]['count']++;
        }
        ksort($rows);

        return ['unit' => $unit, 'rows' => array_values($rows)];
    }

    /** Open invoices (unpaid / partial) with their age in days, oldest first. */
    private function unpaidInvoices(): array
    {
        $rows = (new InvoiceModel())
            ->whereIn('status', [InvoiceModel::STATUS_UNPAID, InvoiceModel::STATUS_PARTIAL])
            ->where('balance >', 0)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $aging = ['0-7' => 0.0, '8-30' => 0.0, '31+' => 0.0];
        $total = 0.0;
        foreach ($rows as &$r) {
            $r['age'] = (int) floor((strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime((string) $r['created_at'])))) / 86400);
            $bal      = (float) $r['balance'];
            $total   += $bal;
            if ($r['age'] <= 7)      $aging['0-7']  += $bal;
            elseif ($r['age'] <= 30) $aging['8-30'] += $bal;
            else                     $aging['31+']  += $bal;
        }
        unset($r);

        return ['rows' => $rows, 'total' => $total, 'aging' => $aging];
    }
}
