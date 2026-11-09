<?php

namespace App\Commands;

use App\Models\PatientModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Repair patients who were merged into someone else's record.
 *
 * Until 2026-11-09, approving a booking or registering a walk-in matched an
 * existing patient by email or phone alone. Family members who share a
 * phone (a parent booking for a child) were handed the other person's
 * patient code, so their requests, payments and invoices were filed under
 * the wrong patient, and they never got a record of their own.
 *
 * For every diagnostic request whose patient_code belongs to a patient with
 * a different name, this finds or creates the right patient (same rule as
 * PatientModel::findSamePerson) and moves that request, plus its payments
 * and invoices, onto the right code. It also creates records for approved
 * or completed appointments whose patient was never created.
 *
 *   php spark patients:repair-codes --dry-run   show what would change
 *   php spark patients:repair-codes             apply it
 */
class RepairPatientCodes extends BaseCommand
{
    protected $group       = 'Patients';
    protected $name        = 'patients:repair-codes';
    protected $description = 'Give patients merged into another record by a shared phone or email their own code.';
    protected $usage       = 'patients:repair-codes [--dry-run]';
    protected $options     = ['--dry-run' => 'List the changes without writing anything.'];

    public function run(array $params)
    {
        $dry      = CLI::getOption('dry-run') !== null || in_array('--dry-run', $params, true);
        $db       = db_connect();
        $patients = new PatientModel();
        $norm     = static fn ($s) => preg_replace('/\s+/', ' ', strtolower(trim((string) $s)));

        $byCode = [];
        foreach ($patients->findAll() as $p) {
            $byCode[(string) $p['patient_code']] = $p;
        }

        $fixed = 0;

        // 1. Requests filed under another person's code
        $requests = $db->table('diagnostic_requests')
            ->select('id, reference_number, type, appointment_id, patient_name, patient_code, age, gender, email, phone')
            ->where('patient_code IS NOT NULL')
            ->get()->getResultArray();

        foreach ($requests as $r) {
            $owner = $byCode[(string) $r['patient_code']] ?? null;
            if (! $owner || $norm($owner['full_name']) === $norm($r['patient_name'])) {
                continue;
            }

            $right = $patients->findSamePerson(
                (string) $r['patient_name'], $r['age'], (string) $r['gender'],
                trim((string) $r['email']), trim((string) $r['phone'])
            );
            $newCode = $right['patient_code'] ?? '(new code)';

            CLI::write(sprintf(
                '%s  %-28s filed under %s (%s)  ->  %s',
                str_pad((string) $r['reference_number'], 14),
                $r['patient_name'], $r['patient_code'], $owner['full_name'], $newCode
            ), 'yellow');

            if ($dry) {
                $fixed++;
                continue;
            }

            if (! $right) {
                $right = $this->createPatient($patients, $r, $r['appointment_id'] ? 'online' : 'walk-in');
                if (! $right) {
                    CLI::error('  could not create a patient record; skipped');
                    continue;
                }
                $byCode[$right['patient_code']] = $right;
                CLI::write('  created ' . $right['patient_code'], 'green');
            }

            $db->transStart();
            $db->table('diagnostic_requests')->where('id', $r['id'])->update(['patient_code' => $right['patient_code']]);
            $db->table('payments')
                ->where('request_id', $r['id'])->where('request_type', $r['type'])
                ->where('patient_code', $r['patient_code'])
                ->update(['patient_code' => $right['patient_code']]);
            $db->table('invoices')
                ->where('request_id', $r['id'])->where('request_type', $r['type'])
                ->where('patient_code', $r['patient_code'])
                ->update(['patient_code' => $right['patient_code']]);
            $db->transComplete();

            $fixed++;
        }

        // 2. Approved / completed bookings whose patient was never created
        $appointments = $db->table('appointments')
            ->select('id, reference_number, full_name, age, gender, email, phone')
            ->whereIn('status', ['approved', 'completed'])
            ->get()->getResultArray();

        foreach ($appointments as $a) {
            if ($patients->findSamePerson((string) $a['full_name'], $a['age'], (string) $a['gender'],
                trim((string) $a['email']), trim((string) $a['phone']))) {
                continue;
            }

            CLI::write(sprintf('%s  %-28s approved but has no patient record  ->  (new code)',
                str_pad(substr((string) $a['reference_number'], 0, 14), 14), $a['full_name']), 'yellow');

            if (! $dry) {
                $created = $patients->findOrCreateFromAppointment($a);
                CLI::write('  created ' . ($created['patient_code'] ?? 'nothing (see log)'), $created ? 'green' : 'red');
            }
            $fixed++;
        }

        CLI::newLine();
        CLI::write($fixed === 0
            ? 'Nothing to repair.'
            : ($dry ? "$fixed record(s) would be repaired. Run without --dry-run to apply." : "$fixed record(s) repaired."),
            $fixed === 0 ? 'green' : 'white');
    }

    private function createPatient(PatientModel $patients, array $r, string $source): ?array
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                $patients->insert([
                    'patient_code' => $patients->generatePatientCode(),
                    'full_name'    => trim((string) $r['patient_name']),
                    'email'        => trim((string) $r['email']) ?: null,
                    'phone'        => trim((string) $r['phone']) ?: null,
                    'age'          => $r['age'],
                    'gender'       => $r['gender'],
                    'source'       => $source,
                ]);
                return $patients->find($patients->getInsertID()) ?: null;
            } catch (\Throwable $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    log_message('error', 'patients:repair-codes: ' . $e->getMessage());
                    return null;
                }
            }
        }
        return null;
    }
}
