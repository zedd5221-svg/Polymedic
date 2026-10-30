<?php

namespace App\Controllers\Concerns;

use App\Models\DiagnosticRequestModel;
use App\Models\PaymentModel;

/**
 * Workload / turnaround report for a diagnostic department (Radiology,
 * Laboratory): the on-screen Reports page and the printable A4 paper.
 *
 * - Received: paid requests whose request_date falls in the window.
 * - Released: released_at in the window.
 * - Turnaround (TAT): first payment -> release, the time the request
 *   actually spent with the department.
 * - Backlog: every unreleased request, as of now.
 *
 * The using controller supplies reportDepartment(): the request type,
 * its routes, TAT targets and the wording the views use.
 * GET: range (today|yesterday|week|month|last_month|custom), from, to,
 *      <staffParam>, export=csv (page), annex=0 (paper)
 */
trait HandlesDepartmentReports
{
    use ResolvesReportPeriod;

    /**
     * Department config. Keys:
     *   type, base (route prefix), view, staffParam, tat [stat, urgent, routine hours],
     *   name, unitName, title, paperTitle, prefix,
     *   case [singular, plural], item [singular, plural], staff [title, plural],
     *   status [dept status => label], revenueLabel,
     *   worklist, open, print (routes), images (bool), sections (bool),
     *   signs [[role, title] x3]
     */
    abstract protected function reportDepartment(): array;

    /** On-screen Reports page (or its CSV export). */
    private function departmentReportPage()
    {
        $data = $this->departmentReportData();

        if ($this->request->getGet('export') === 'csv') {
            return $this->departmentCsv($data['studies'], $data['from'], $data['to']);
        }

        return view($data['dept']['view'], $data);
    }

    /** Printable A4 department report. */
    private function departmentReportPaper()
    {
        $data = $this->departmentReportData();
        $dept = $data['dept'];

        return view('Reports/department', $data + [
            'reportNo' => $dept['prefix'] . '-' . date('ymd', strtotime($data['from']))
                        . ($data['from'] !== $data['to'] ? '-' . date('ymd', strtotime($data['to'])) : ''),
            'annex'    => $this->request->getGet('annex') !== '0',
            'backUrl'  => base_url($dept['base'] . '/reports') . '?' . $data['query'],
        ]);
    }

    /** Everything both views need for the requested window. */
    private function departmentReportData(): array
    {
        $dept = $this->reportDepartment();
        $cols = DiagnosticRequestModel::STAFF_COLUMNS[$dept['type']];

        [$range, $from, $to] = $this->reportRange('month');
        $staff = trim((string) ($this->request->getGet($dept['staffParam']) ?? ''));

        [$days, $prevFrom, $prevTo] = $this->previousWindow($from, $to);

        $studies  = $this->reportStudies($from, $to, $staff);
        $released = $this->releasedStudies($from, $to, $staff);

        $staffList = array_column(
            db_connect()->table('diagnostic_requests')
                ->select($cols['name'] . ' AS name')->distinct()
                ->where('type', $dept['type'])
                ->where($cols['name'] . ' IS NOT NULL')->where($cols['name'] . ' <>', '')
                ->orderBy($cols['name'])->get()->getResultArray(),
            'name'
        );

        $payments = new PaymentModel();

        return [
            'dept'        => $dept,
            'staffColumn' => $cols['name'],
            'range'       => $range,
            'from'        => $from,
            'to'          => $to,
            'days'        => $days,
            'prevFrom'    => $prevFrom,
            'prevTo'      => $prevTo,
            'periodLabel' => self::periodLabel($from, $to),
            'prevLabel'   => self::periodLabel($prevFrom, $prevTo),
            'staff'       => $staff,
            'staffList'   => $staffList,
            'query'       => http_build_query(array_filter([
                'range' => $range, 'from' => $from, 'to' => $to, $dept['staffParam'] => $staff,
            ], fn($v) => $v !== '')),
            'studies'     => $studies,
            'released'    => $released,
            'summary'     => $this->summariseStudies($studies, $released),
            'prev'        => $this->summariseStudies(
                $this->reportStudies($prevFrom, $prevTo, $staff),
                $this->releasedStudies($prevFrom, $prevTo, $staff)
            ),
            'revenue'     => $payments->getRevenueBetween($dept['type'], $from, $to),
            'prevRevenue' => $payments->getRevenueBetween($dept['type'], $prevFrom, $prevTo),
            'daily'       => $this->dailyVolume($studies, $released, $from, $to),
            'backlog'     => $this->backlog($staff),
            'targets'     => $dept['tat'],
            'clinic'      => config('Clinic')->toArray(),
            'preparedBy'  => PaymentModel::currentCashierName(),
        ];
    }

    /** Paid requests with a request date in the window, decorated. */
    private function reportStudies(string $from, string $to, string $staff = ''): array
    {
        $dept    = $this->reportDepartment();
        $builder = (new DiagnosticRequestModel())
            ->where('type', $dept['type'])
            ->whereIn('status', DiagnosticRequestModel::DEPARTMENT_STATUSES)
            ->where('request_date >=', $from)
            ->where('request_date <=', $to);

        if ($staff !== '') {
            $builder->where(DiagnosticRequestModel::STAFF_COLUMNS[$dept['type']]['name'], $staff);
        }

        return $this->decorateStudies($builder->orderBy('request_date', 'DESC')->orderBy('id', 'DESC')->findAll());
    }

    /** Requests released in the window (whatever their request date), decorated. */
    private function releasedStudies(string $from, string $to, string $staff = ''): array
    {
        $dept    = $this->reportDepartment();
        $builder = (new DiagnosticRequestModel())
            ->where('type', $dept['type'])
            ->where('status', DiagnosticRequestModel::STATUS_RELEASED)
            ->where('released_at >=', $from . ' 00:00:00')
            ->where('released_at <=', $to . ' 23:59:59');

        if ($staff !== '') {
            $builder->where(DiagnosticRequestModel::STAFF_COLUMNS[$dept['type']]['name'], $staff);
        }

        return $this->decorateStudies($builder->orderBy('released_at', 'DESC')->findAll());
    }

    /**
     * Adds dept_status, items[], image_count, staff, paid_at, tat_hours,
     * priority_key and within_target to each row.
     */
    private function decorateStudies(array $rows): array
    {
        if ($rows === []) return [];

        $dept    = $this->reportDepartment();
        $targets = $dept['tat'];
        $col     = DiagnosticRequestModel::STAFF_COLUMNS[$dept['type']]['name'];
        $rows    = DiagnosticRequestModel::withDepartmentStatus($rows);

        // When the request reached the department = its first payment.
        $paidAt = [];
        foreach (db_connect()->table('payments')
            ->select('request_id, MIN(payment_date) AS paid_at')
            ->where('request_type', $dept['type'])
            ->whereIn('payment_status', [PaymentModel::STATUS_PAID, PaymentModel::STATUS_REFUNDED])
            ->whereIn('request_id', array_column($rows, 'id'))
            ->groupBy('request_id')->get()->getResultArray() as $p) {
            $paidAt[(int) $p['request_id']] = $p['paid_at'];
        }

        foreach ($rows as &$r) {
            $r['items'] = array_values(array_filter(array_map('trim', explode(',', (string) ($r['services'] ?? '')))));
            $r['staff'] = trim((string) ($r[$col] ?? ''));

            $images = json_decode((string) ($r['image_paths'] ?? ''), true);
            $r['image_count'] = is_array($images) ? count($images) : (empty($r['image_path']) ? 0 : 1);

            $r['paid_at'] = $paidAt[(int) $r['id']] ?? $r['created_at'];

            $p = strtolower(trim((string) ($r['priority'] ?? '')));
            $r['priority_key'] = isset($targets[$p]) ? $p : 'routine';

            $r['tat_hours']     = null;
            $r['within_target'] = null;
            if (!empty($r['released_at']) && !empty($r['paid_at'])) {
                $r['tat_hours']     = max(0, (strtotime($r['released_at']) - strtotime($r['paid_at'])) / 3600);
                $r['within_target'] = $r['tat_hours'] <= $targets[$r['priority_key']];
            }
        }
        unset($r);

        return $rows;
    }

    /** Totals and breakdowns for a window. */
    private function summariseStudies(array $studies, array $released): array
    {
        $dept = $this->reportDepartment();
        $s = [
            'received' => count($studies), 'released' => count($released),
            'items' => 0, 'patients' => 0, 'stat' => 0,
            'status' => ['pending' => 0, 'in_progress' => 0, 'draft' => 0, 'completed' => 0, 'released' => 0],
            'tat_median' => null, 'tat_avg' => null, 'within_target' => null,
            'tat_buckets' => ['< 2 h' => 0, '2–8 h' => 0, '8–24 h' => 0, '1–3 days' => 0, '> 3 days' => 0],
            'top' => [], 'sections' => [], 'staff' => [], 'release_rate' => null,
            'priority' => [],
        ];
        foreach (array_keys($dept['tat']) as $key) {
            $s['priority'][$key] = ['released' => 0, 'within' => 0, 'tats' => [], 'median' => null];
        }

        $patients = [];
        foreach ($studies as $r) {
            $st = $r['status'];
            if (isset($s['status'][$st])) $s['status'][$st]++;
            if ($r['priority_key'] !== 'routine') $s['stat']++;
            $s['items'] += count($r['items']);
            foreach ($r['items'] as $e) {
                $s['top'][$e] = ($s['top'][$e] ?? 0) + 1;
                if (!empty($dept['sections'])) {
                    $sec = DiagnosticRequestModel::labSection($e);
                    $s['sections'][$sec] = ($s['sections'][$sec] ?? 0) + 1;
                }
            }
            $key = trim((string) ($r['patient_code'] ?? '')) ?: strtolower(trim((string) $r['patient_name']));
            if ($key !== '') $patients[$key] = true;
        }
        $s['patients'] = count($patients);
        if ($s['received'] > 0) {
            $s['release_rate'] = $s['status']['released'] / $s['received'] * 100;
        }
        arsort($s['top']);
        arsort($s['sections']);

        $tats = [];
        $hits = 0;
        foreach ($released as $r) {
            $who = $r['staff'] ?: 'Unassigned';
            $s['staff'][$who]['count'] = ($s['staff'][$who]['count'] ?? 0) + 1;
            $s['staff'][$who]['items'] = ($s['staff'][$who]['items'] ?? 0) + count($r['items']);
            $s['priority'][$r['priority_key']]['released']++;
            if ($r['tat_hours'] === null) continue;

            $h = $r['tat_hours'];
            $tats[] = $h;
            $s['staff'][$who]['tat'][] = $h;
            $s['priority'][$r['priority_key']]['tats'][] = $h;
            if ($r['within_target']) {
                $hits++;
                $s['priority'][$r['priority_key']]['within']++;
            }

            if ($h < 2)       $s['tat_buckets']['< 2 h']++;
            elseif ($h < 8)   $s['tat_buckets']['2–8 h']++;
            elseif ($h < 24)  $s['tat_buckets']['8–24 h']++;
            elseif ($h < 72)  $s['tat_buckets']['1–3 days']++;
            else              $s['tat_buckets']['> 3 days']++;
        }

        if ($tats) {
            $s['tat_median']    = self::median($tats);
            $s['tat_avg']       = array_sum($tats) / count($tats);
            $s['within_target'] = $hits / count($tats) * 100;
        }
        foreach ($s['priority'] as &$pr) {
            $pr['median'] = $pr['tats'] ? self::median($pr['tats']) : null;
            unset($pr['tats']);
        }
        unset($pr);

        foreach ($s['staff'] as &$rd) {
            $t = $rd['tat'] ?? [];
            $rd['tat_avg'] = $t ? array_sum($t) / count($t) : null;
            unset($rd['tat']);
        }
        unset($rd);
        uasort($s['staff'], fn($a, $b) => $b['count'] <=> $a['count']);

        return $s;
    }

    private static function median(array $values): float
    {
        sort($values);
        $n = count($values);
        return $n % 2 ? $values[intdiv($n, 2)] : ($values[$n / 2 - 1] + $values[$n / 2]) / 2;
    }

    /** Received vs released per day (or per hour for a single day). */
    private function dailyVolume(array $studies, array $released, string $from, string $to): array
    {
        $hourly  = $from === $to;
        $buckets = [];

        if ($hourly) {
            for ($h = 7; $h <= 19; $h++) {
                $buckets[sprintf('%02d', $h)] = ['label' => date('g A', mktime($h, 0, 0)), 'received' => 0, 'released' => 0];
            }
        } else {
            for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
                $buckets[$d] = ['label' => date('M j', strtotime($d)), 'date' => $d, 'received' => 0, 'released' => 0];
            }
        }

        $add = function (string $when, string $field) use (&$buckets, $hourly) {
            $ts  = strtotime($when);
            $key = $hourly ? date('H', $ts) : date('Y-m-d', $ts);
            if (!isset($buckets[$key])) {
                if (!$hourly) return; // released row outside the window's days
                $buckets[$key] = ['label' => date('g A', $ts), 'received' => 0, 'released' => 0];
            }
            $buckets[$key][$field]++;
        };

        // A single day has one request date, so spread it by the time paid.
        foreach ($studies as $r)  $add($hourly ? (string) $r['paid_at'] : (string) $r['request_date'], 'received');
        foreach ($released as $r) $add((string) $r['released_at'], 'released');
        ksort($buckets);

        return ['hourly' => $hourly, 'buckets' => array_values($buckets)];
    }

    /** Every unreleased request as of now, oldest first, with its age in hours. */
    private function backlog(string $staff = ''): array
    {
        $dept    = $this->reportDepartment();
        $cols    = DiagnosticRequestModel::STAFF_COLUMNS[$dept['type']];
        $builder = (new DiagnosticRequestModel())
            ->where('type', $dept['type'])
            ->whereIn('status', [
                DiagnosticRequestModel::STATUS_IN_PROGRESS,
                DiagnosticRequestModel::STATUS_DRAFT,
                DiagnosticRequestModel::STATUS_COMPLETED,
            ]);

        if ($staff !== '') {
            // Untouched requests have no one assigned yet but are everyone's queue.
            $builder->groupStart()
                    ->where($cols['name'], $staff)
                    ->orGroupStart()
                        ->where('status', DiagnosticRequestModel::STATUS_IN_PROGRESS)
                        ->where($cols['id'], null)
                        ->groupStart()->where($cols['name'], null)->orWhere($cols['name'], '')->groupEnd()
                    ->groupEnd()
                    ->groupEnd();
        }

        $rows  = $this->decorateStudies($builder->orderBy('created_at', 'ASC')->findAll());
        $aging = ['< 24 h' => 0, '1–3 days' => 0, '> 3 days' => 0];
        $over  = 0;

        foreach ($rows as &$r) {
            $r['age_hours'] = max(0, (time() - strtotime((string) $r['paid_at'])) / 3600);
            $r['overdue']   = $r['age_hours'] > $dept['tat'][$r['priority_key']];
            if ($r['overdue']) $over++;
            if ($r['age_hours'] < 24)     $aging['< 24 h']++;
            elseif ($r['age_hours'] < 72) $aging['1–3 days']++;
            else                          $aging['> 3 days']++;
        }
        unset($r);
        usort($rows, fn($a, $b) => $b['age_hours'] <=> $a['age_hours']);

        return ['rows' => $rows, 'aging' => $aging, 'overdue' => $over];
    }

    /** Request log for the window as a CSV download. */
    private function departmentCsv(array $studies, string $from, string $to)
    {
        $dept = $this->reportDepartment();
        $itemTitle = ucfirst($dept['item'][1]);

        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF"); // BOM so Excel reads ñ correctly
        fputcsv($fh, array_merge(
            ['Request Date', 'Reference', 'Patient Code', 'Patient', 'Age', 'Sex', $itemTitle, 'Count', 'Priority', 'Status', $dept['staff'][0]],
            $dept['images'] ? ['Images'] : [],
            ['Received (paid)', 'Released', 'TAT (hours)', 'Within Target']
        ));

        foreach ($studies as $r) {
            fputcsv($fh, array_merge(
                [
                    $r['request_date'], $r['reference_number'], $r['patient_code'] ?? '', $r['patient_name'],
                    $r['age'] ?? '', $r['gender'] ?? '', implode('; ', $r['items']), count($r['items']),
                    ucfirst($r['priority_key']), ucwords(str_replace('_', ' ', $r['status'])), $r['staff'],
                ],
                $dept['images'] ? [$r['image_count']] : [],
                [
                    $r['paid_at'] ?? '', $r['released_at'] ?? '',
                    $r['tat_hours'] === null ? '' : number_format($r['tat_hours'], 1, '.', ''),
                    $r['within_target'] === null ? '' : ($r['within_target'] ? 'Yes' : 'No'),
                ]
            ));
        }

        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        $name = strtolower($dept['name']) . '-report_' . $from . ($from !== $to ? '_to_' . $to : '') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            ->setBody($csv);
    }
}
