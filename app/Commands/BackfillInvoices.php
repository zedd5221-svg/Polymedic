<?php

namespace App\Commands;

use App\Models\InvoiceModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Give every paid payment an invoice.
 *
 * Payments taken with the Start button on Diagnostic Requests are invoiced
 * automatically; this command covers payments made before that existed, or
 * any whose invoice could not be created at the time (the error is logged).
 * It reuses a request's existing invoice when there is one, so it is safe
 * to run more than once.
 *
 *   php spark invoices:backfill --dry-run   list what would be invoiced
 *   php spark invoices:backfill             create the invoices
 */
class BackfillInvoices extends BaseCommand
{
    protected $group       = 'Billing';
    protected $name        = 'invoices:backfill';
    protected $description = 'Create invoices for paid payments that do not have one.';
    protected $usage       = 'invoices:backfill [--dry-run]';
    protected $options     = ['--dry-run' => 'List the payments without creating anything.'];

    public function run(array $params)
    {
        $dryRun = CLI::getOption('dry-run') !== null || in_array('--dry-run', $params, true);

        $payments = db_connect()->table('payments')
            ->select('id, reference_number, patient_name, request_type, request_id, total_amount, amount_paid, payment_date')
            ->where('invoice_id IS NULL')
            ->where('payment_status', 'paid')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        if (!$payments) {
            CLI::write('Every paid payment already has an invoice.', 'green');
            return;
        }

        CLI::write(count($payments) . ' paid payment(s) without an invoice' . ($dryRun ? ' (dry run, nothing is changed)' : '') . ':');

        $model = new InvoiceModel();
        $made  = 0;
        foreach ($payments as $p) {
            $line = sprintf('  #%d %s  %s  %s  P%s',
                $p['id'], $p['reference_number'] ?? '', $p['patient_name'] ?? '',
                strtoupper((string) ($p['request_type'] ?? '-')), number_format((float) $p['amount_paid'], 2));

            if ($dryRun) { CLI::write($line); continue; }

            try {
                $invoiceId = $model->syncFromDiagnosticPayment((int) $p['id']);
            } catch (\Throwable $e) {
                $invoiceId = null;
                log_message('error', 'invoices:backfill payment #' . $p['id'] . ': ' . $e->getMessage());
            }

            if ($invoiceId) {
                $inv = $model->find($invoiceId);
                CLI::write($line . '  ->  ' . ($inv['invoice_no'] ?? '#' . $invoiceId) . ' (' . ($inv['status'] ?? '?') . ')', 'green');
                $made++;
            } else {
                CLI::write($line . '  ->  failed, see the log', 'red');
            }
        }

        if (!$dryRun) {
            CLI::write($made . ' of ' . count($payments) . ' payment(s) invoiced.', $made === count($payments) ? 'green' : 'yellow');
        }
    }
}
