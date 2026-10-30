<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Same-role patient handoffs.
 *
 * A med tech hands lab requests to another med tech; a radiologist
 * hands x-ray requests to another radiologist. What can be handed off:
 * requests the sender is handling, and pending ones nobody holds yet. The sender
 * picks the patients and gives a reason, the recipient is notified and
 * accepts or declines, and the sender is told the answer. Requests only
 * change hands on Accept.
 *
 * While a transfer is pending its requests can't go into another one
 * (unique patient_transfer_items.pending_request_id). On Accept each
 * request is re-checked: one the sender no longer has (finished,
 * cancelled, reassigned) is skipped and reported, not moved.
 */
class PatientTransferModel extends Model
{
    protected $table         = 'patient_transfers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'type', 'from_user_id', 'from_user_name', 'to_user_id', 'to_user_name',
        'reason', 'status', 'responded_at',
    ];

    const STATUS_PENDING   = 'pending';
    const STATUS_ACCEPTED  = 'accepted';
    const STATUS_DECLINED  = 'declined';
    const STATUS_CANCELLED = 'cancelled';

    const ITEMS_TABLE = 'patient_transfer_items';

    // ----------------------------------------------------------------
    // What the transfer form offers
    // ----------------------------------------------------------------

    /**
     * Requests of this type the user could hand off, not already waiting
     * in a pending transfer. Each row carries its department 'stage':
     * pending (paid, nobody started), in_progress or draft.
     */
    public function transferableRequests(int $userId, string $type): array
    {
        $rows = $this->db->table('diagnostic_requests')
            ->select('id, type, reference_number, patient_name, age, gender, services, status,
                      med_tech_id, med_tech_name, radiologist_id, radiologist_name')
            ->where('type', $type)
            ->where($this->transferableSql($type, $userId), null, false)
            ->where('id NOT IN (SELECT pending_request_id FROM ' . self::ITEMS_TABLE . ' WHERE pending_request_id IS NOT NULL)', null, false)
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['stage'] = DiagnosticRequestModel::departmentStatus($row);
        }
        unset($row);
        return $rows;
    }

    /**
     * SQL condition for what $userId may hand off: active requests they
     * handle, plus pending (paid, untouched) ones nobody holds yet — those
     * date from before Start assigned a user, and any user of the
     * department may pass them to a colleague.
     */
    private function transferableSql(string $type, int $userId): string
    {
        $handler = DiagnosticRequestModel::HANDLER_ID_SQL;

        return "(status IN ('" . implode("','", DiagnosticRequestModel::ACTIVE_STATUSES) . "')"
            . " AND ({$handler} = " . $userId
            . " OR ({$handler} IS NULL AND " . DiagnosticRequestModel::untouchedSql($type) . ')))';
    }

    /**
     * Colleagues who can receive a transfer: active users of the same
     * role, minus the sender, with their active workload.
     *
     * @return list<array{id: int, name: string, role: string, workload: int}>
     */
    public function recipients(int $userId, string $type): array
    {
        $rows = (new UserModel())
            ->select('id, full_name, role')
            ->whereIn('role', DiagnosticRequestModel::ASSIGNABLE_ROLES[$type])
            ->where('status', 'active')
            ->where('id !=', $userId)
            ->where('full_name IS NOT NULL')
            ->where('full_name <>', '')
            ->orderBy('full_name', 'ASC')
            ->findAll();

        $load = (new DiagnosticRequestModel())->activeWorkload(array_map('intval', array_column($rows, 'id')));

        return array_map(static fn (array $u): array => [
            'id'       => (int) $u['id'],
            'name'     => trim((string) $u['full_name']),
            'role'     => (string) $u['role'],
            'workload' => $load[(int) $u['id']] ?? 0,
        ], $rows);
    }

    /** Pending transfers sent to / by a user, with their patients. */
    public function pendingFor(int $userId, string $type, string $direction): array
    {
        $rows = $this->where('type', $type)
                     ->where('status', self::STATUS_PENDING)
                     ->where($direction === 'incoming' ? 'to_user_id' : 'from_user_id', $userId)
                     ->orderBy('created_at', 'DESC')
                     ->findAll();

        return $this->withItems($rows);
    }

    /** Of these transfer ids, the ones still waiting for this user's answer. */
    public function awaitingAnswerFrom(int $userId, array $transferIds): array
    {
        if ($userId <= 0 || $transferIds === []) {
            return [];
        }
        $rows = $this->db->table($this->table)
            ->select('id')
            ->whereIn('id', array_map('intval', $transferIds))
            ->where('to_user_id', $userId)
            ->where('status', self::STATUS_PENDING)
            ->get()->getResultArray();

        return array_map('intval', array_column($rows, 'id'));
    }

    private function withItems(array $transfers): array
    {
        if ($transfers === []) {
            return [];
        }
        $items = $this->db->table(self::ITEMS_TABLE)
            ->whereIn('transfer_id', array_column($transfers, 'id'))
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $byTransfer = [];
        foreach ($items as $item) {
            $byTransfer[$item['transfer_id']][] = $item;
        }
        foreach ($transfers as &$t) {
            $t['items'] = $byTransfer[$t['id']] ?? [];
        }
        unset($t);
        return $transfers;
    }

    // ----------------------------------------------------------------
    // Actions — each returns ['success' => bool, 'message' => string]
    // ----------------------------------------------------------------

    /**
     * Ask $toUserId to take over these requests.
     *
     * @param int[] $requestIds
     */
    public function createTransfer(array $sender, int $toUserId, string $type, array $requestIds, string $reason): array
    {
        $reason     = trim($reason);
        $requestIds = array_values(array_unique(array_filter(array_map('intval', $requestIds))));

        if ($requestIds === []) {
            return $this->fail('Select at least one patient to transfer.');
        }
        if ($reason === '') {
            return $this->fail('Enter the reason for the transfer.');
        }
        if (mb_strlen($reason) > 1000) {
            return $this->fail('Keep the reason under 1000 characters.');
        }

        $recipient = null;
        foreach ($this->recipients((int) $sender['id'], $type) as $r) {
            if ($r['id'] === $toUserId) {
                $recipient = $r;
            }
        }
        if ($recipient === null) {
            return $this->fail('Choose an active colleague with the same role to receive the patients.');
        }

        // Only requests the sender is handling right now.
        $eligible = [];
        foreach ($this->transferableRequests((int) $sender['id'], $type) as $row) {
            $eligible[(int) $row['id']] = $row;
        }
        $missing = array_diff($requestIds, array_keys($eligible));
        if ($missing !== []) {
            return $this->fail(count($missing) === 1
                ? 'One of the selected patients is no longer yours to transfer, or is already in a pending transfer. Refresh and try again.'
                : count($missing) . ' of the selected patients are no longer yours to transfer, or are already in a pending transfer. Refresh and try again.');
        }

        $this->db->transBegin();
        try {
            $created = $this->insert([
                'type'           => $type,
                'from_user_id'   => (int) $sender['id'],
                'from_user_name' => (string) $sender['name'],
                'to_user_id'     => $recipient['id'],
                'to_user_name'   => $recipient['name'],
                'reason'         => $reason,
                'status'         => self::STATUS_PENDING,
            ]);
            if (! $created) {
                throw new \RuntimeException('transfer insert failed');
            }
            $transferId = (int) $this->getInsertID();

            foreach ($requestIds as $rid) {
                // A duplicate pending_request_id means another transfer
                // claimed this request a moment ago.
                $ok = $this->db->table(self::ITEMS_TABLE)->insert([
                    'transfer_id'        => $transferId,
                    'request_id'         => $rid,
                    'pending_request_id' => $rid,
                    'patient_name'       => (string) $eligible[$rid]['patient_name'],
                    'reference_number'   => $eligible[$rid]['reference_number'],
                ]);
                if (! $ok) {
                    throw new \RuntimeException('request #' . $rid . ' is already in a pending transfer');
                }
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Patient transfer create failed: ' . $e->getMessage());
            return $this->fail('One of these patients was just put in another transfer. Refresh and try again.');
        }

        $names = array_map(static fn ($id) => (string) $eligible[$id]['patient_name'], $requestIds);
        NotificationModel::dispatchToUser(
            'transfer',
            'Patient transfer request from ' . $sender['name'],
            $sender['name'] . ' wants to transfer ' . $this->patientCount(count($names)) . ' to you: '
                . $this->nameList($names) . '. Reason: ' . $reason,
            $transferId,
            $recipient['id'],
            $recipient['role']
        );

        return [
            'success' => true,
            'message' => 'Transfer request sent to ' . $recipient['name'] . '. The ' . (count($names) === 1 ? 'patient stays' : 'patients stay')
                . ' with you until they accept.',
        ];
    }

    /** Recipient's Accept (true) or Decline (false). */
    public function respond(int $transferId, array $recipient, bool $accept): array
    {
        $transfer = $this->find($transferId);
        if (! $transfer || (int) $transfer['to_user_id'] !== (int) $recipient['id']) {
            return $this->fail('Transfer request not found.');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->transBegin();
        try {
            // Compare-and-set, so a double click or a cancel at the same
            // moment can't answer the same transfer twice.
            $this->db->table($this->table)
                ->where('id', $transferId)
                ->where('status', self::STATUS_PENDING)
                ->update([
                    'status'       => $accept ? self::STATUS_ACCEPTED : self::STATUS_DECLINED,
                    'responded_at' => $now,
                    'updated_at'   => $now,
                ]);

            if ($this->db->affectedRows() !== 1) {
                $this->db->transRollback();
                $current = $this->find($transferId);
                return $this->fail('This transfer was already ' . ($current['status'] ?? 'answered') . '.');
            }

            $moved = $skipped = [];
            foreach ($this->db->table(self::ITEMS_TABLE)->where('transfer_id', $transferId)->get()->getResultArray() as $item) {
                $ok = $accept && $this->moveRequest((int) $item['request_id'], $transfer, $now);
                $this->db->table(self::ITEMS_TABLE)->where('id', $item['id'])->update([
                    'pending_request_id' => null,
                    'outcome'            => $accept ? ($ok ? 'moved' : 'skipped') : null,
                ]);
                if ($accept && $ok) {
                    $moved[] = $item['patient_name'];
                } elseif ($accept) {
                    $skipped[] = $item['patient_name'];
                }
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        // The request notification has been answered.
        $this->db->table('notifications')
            ->where('type', 'transfer')->where('reference_id', (string) $transferId)->where('user_id', (int) $recipient['id'])
            ->update(['is_read' => 1]);

        $senderRole = (string) ((new UserModel())->select('role')->find((int) $transfer['from_user_id'])['role'] ?? '');

        if (! $accept) {
            NotificationModel::dispatchToUser(
                'transfer',
                'Transfer declined by ' . $recipient['name'],
                $recipient['name'] . ' declined your transfer of ' . $this->nameList(array_column($this->itemsOf($transferId), 'patient_name'))
                    . '. The patients stay with you.',
                $transferId,
                (int) $transfer['from_user_id'],
                $senderRole
            );
            return ['success' => true, 'message' => 'Transfer declined. ' . $transfer['from_user_name'] . ' has been notified.'];
        }

        $note = $skipped !== []
            ? ' Not moved (no longer with ' . $transfer['from_user_name'] . '): ' . $this->nameList($skipped) . '.'
            : '';
        NotificationModel::dispatchToUser(
            'transfer',
            'Transfer accepted by ' . $recipient['name'],
            $recipient['name'] . ' accepted your transfer'
                . ($moved !== [] ? ' and now handles ' . $this->nameList($moved) . '.' : ', but none of the patients were still with you.')
                . $note,
            $transferId,
            (int) $transfer['from_user_id'],
            $senderRole
        );

        return [
            'success' => true,
            'message' => $moved !== []
                ? 'Transfer accepted. You now handle ' . $this->nameList($moved) . '.' . $note
                : 'Transfer accepted, but none of the patients were still with ' . $transfer['from_user_name'] . '.',
        ];
    }

    /** Sender withdraws a request the recipient hasn't answered yet. */
    public function cancelTransfer(int $transferId, array $sender): array
    {
        $transfer = $this->find($transferId);
        if (! $transfer || (int) $transfer['from_user_id'] !== (int) $sender['id']) {
            return $this->fail('Transfer request not found.');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->transBegin();
        $this->db->table($this->table)
            ->where('id', $transferId)
            ->where('status', self::STATUS_PENDING)
            ->update(['status' => self::STATUS_CANCELLED, 'responded_at' => $now, 'updated_at' => $now]);

        if ($this->db->affectedRows() !== 1) {
            $this->db->transRollback();
            $current = $this->find($transferId);
            return $this->fail($transfer['to_user_name'] . ' already ' . ($current['status'] ?? 'answered') . ' this transfer.');
        }
        $this->db->table(self::ITEMS_TABLE)->where('transfer_id', $transferId)->update(['pending_request_id' => null]);
        $this->db->transCommit();

        $this->db->table('notifications')
            ->where('type', 'transfer')->where('reference_id', (string) $transferId)->where('user_id', (int) $transfer['to_user_id'])
            ->update(['is_read' => 1]);

        $recipientRole = (string) ((new UserModel())->select('role')->find((int) $transfer['to_user_id'])['role'] ?? '');
        NotificationModel::dispatchToUser(
            'transfer',
            'Transfer cancelled by ' . $sender['name'],
            $sender['name'] . ' withdrew the transfer of ' . $this->nameList(array_column($this->itemsOf($transferId), 'patient_name')) . '.',
            $transferId,
            (int) $transfer['to_user_id'],
            $recipientRole
        );

        return ['success' => true, 'message' => 'Transfer request withdrawn.'];
    }

    // ----------------------------------------------------------------

    /**
     * Hand one request to the recipient, only if the sender may still
     * hand it off (theirs and active, or pending and held by nobody).
     * The worked-by columns follow when the sender had already started,
     * so the recipient's name is the one on the finished report.
     */
    private function moveRequest(int $requestId, array $transfer, string $now): bool
    {
        $cols = DiagnosticRequestModel::STAFF_COLUMNS[$transfer['type']];
        $to   = (int) $transfer['to_user_id'];

        $this->db->query(
            "UPDATE diagnostic_requests
                SET assigned_to_id = ?, assigned_to_name = ?, assigned_at = ?, updated_at = ?,
                    {$cols['id']}   = IF({$cols['id']} IS NULL AND COALESCE({$cols['name']}, '') = '', NULL, ?),
                    {$cols['name']} = IF(COALESCE({$cols['name']}, '') = '', {$cols['name']}, ?)
              WHERE id = ? AND type = ? AND " . $this->transferableSql($transfer['type'], (int) $transfer['from_user_id']),
            [$to, $transfer['to_user_name'], $now, $now, $to, $transfer['to_user_name'], $requestId, $transfer['type']]
        );

        return $this->db->affectedRows() === 1;
    }

    private function itemsOf(int $transferId): array
    {
        return $this->db->table(self::ITEMS_TABLE)->where('transfer_id', $transferId)->get()->getResultArray();
    }

    private function patientCount(int $n): string
    {
        return $n . ' patient' . ($n === 1 ? '' : 's');
    }

    private function nameList(array $names): string
    {
        $names = array_values($names);
        if (count($names) <= 1) {
            return (string) ($names[0] ?? '');
        }
        return implode(', ', array_slice($names, 0, -1)) . ' and ' . end($names);
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
