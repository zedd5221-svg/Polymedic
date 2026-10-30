<?php

namespace App\Controllers\Concerns;

/**
 * Report period from the GET params, shared by every report page.
 * GET: range (today|yesterday|week|month|last_month|custom), from, to
 */
trait ResolvesReportPeriod
{
    /** Resolve the report window. Returns [range, from, to]. */
    private function reportRange(string $default = 'today'): array
    {
        $known = ['today', 'yesterday', 'week', 'month', 'last_month', 'custom'];
        $range = (string) ($this->request->getGet('range') ?? $default);
        if (!in_array($range, $known, true)) $range = $default;
        $today = date('Y-m-d');

        switch ($range) {
            case 'yesterday':
                $d = date('Y-m-d', strtotime('-1 day'));
                return [$range, $d, $d];
            case 'week':
                return [$range, date('Y-m-d', strtotime('monday this week')), $today];
            case 'month':
                return [$range, date('Y-m-01'), $today];
            case 'last_month':
                return [$range, date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last day of last month'))];
            case 'custom':
                $from = (string) $this->request->getGet('from');
                $to   = (string) $this->request->getGet('to');
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = $today;
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = $today;
                if ($from > $to) [$from, $to] = [$to, $from];
                // Keep the logs a sane size.
                if ((strtotime($to) - strtotime($from)) / 86400 > 366) {
                    $from = date('Y-m-d', strtotime($to . ' -366 days'));
                }
                return [$range, $from, $to];
            default: // today
                return ['today', $today, $today];
        }
    }

    /** Same-length window just before [from, to]. Returns [days, prevFrom, prevTo]. */
    private function previousWindow(string $from, string $to): array
    {
        $days   = (int) ((strtotime($to) - strtotime($from)) / 86400) + 1;
        $prevTo = date('Y-m-d', strtotime($from . ' -1 day'));

        return [$days, date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' days')), $prevTo];
    }

    /** "October 1–29, 2026", "Oct 30, 2026 – Jan 2, 2027" or a single date. */
    private static function periodLabel(string $from, string $to): string
    {
        if ($from === $to) return date('F j, Y', strtotime($from));
        if (substr($from, 0, 7) === substr($to, 0, 7)) {
            return date('F j', strtotime($from)) . '–' . date('j, Y', strtotime($to));
        }
        return date('M j, Y', strtotime($from)) . ' – ' . date('M j, Y', strtotime($to));
    }
}
