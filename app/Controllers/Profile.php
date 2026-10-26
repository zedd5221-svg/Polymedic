<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\DiagnosticRequestModel;

/*
 * My Profile — one controller shared by every staff role.
 *
 * Routes are registered inside each role's group (admin/profile,
 * receptionist/profile, ...), so the auth filter has already matched
 * the session role to the URL prefix by the time we get here. The
 * role is then used only to pick the right layout and URL prefix.
 *
 * Identity fields (full name, username, role, PRC license, status)
 * stay admin-managed. Lab and X-ray records store the staff member's
 * full_name as plain text and print pages look the PRC license up by
 * that name, so a self-service rename would silently detach a user
 * from their past work.
 */
class Profile extends BaseController
{
    private const ROLES = [
        'admin'        => ['prefix' => 'admin',        'layout' => 'Layouts/AdminLayout',        'section' => 'adminContent'],
        'receptionist' => ['prefix' => 'receptionist', 'layout' => 'Layouts/ReceptionistLayout', 'section' => 'receptionistContent'],
        'radiologist'  => ['prefix' => 'radiologist',  'layout' => 'Layouts/RadiologistLayout',  'section' => 'radiologistContent'],
        'med_tech'     => ['prefix' => 'medtech',      'layout' => 'Layouts/MedTechLayout',      'section' => 'medtechContent'],
    ];

    private const AVATAR_DIR = 'uploads/avatars';

    protected UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    /* ---------------------------------------------------------
       PAGE
       --------------------------------------------------------- */

    public function index()
    {
        $user = $this->currentUser();
        if (! $user) {
            return redirect()->to(base_url('logout'));
        }

        // An admin may have renamed this user since they signed in.
        session()->set(['full_name' => $user['full_name'], 'avatar' => $user['avatar'] ?? null]);

        $role = $this->roleConfig();

        return view('Profile/index', [
            'user'      => $user,
            'roleLabel' => UserModel::roleLabel($user['role']),
            'avatarUrl' => UserModel::avatarUrl($user['avatar'] ?? null),
            'layout'    => $role['layout'],
            'section'   => $role['section'],
            'prefix'    => $role['prefix'],
            'stats'     => $this->activityStats($user),
            'needsPrc'  => $this->users->requiresPrcLicense($user['role']),
        ]);
    }

    /* ---------------------------------------------------------
       PERSONAL INFORMATION
       --------------------------------------------------------- */

    public function update()
    {
        $user = $this->currentUser();
        if (! $user) {
            return redirect()->to(base_url('logout'));
        }

        $rules = [
            'email'          => "required|valid_email|max_length[100]|is_unique[users.email,id,{$user['id']}]",
            'contact_number' => 'permit_empty|max_length[20]|regex_match[/^[0-9+\-\s()]+$/]',
            'gender'         => 'permit_empty|in_list[Male,Female,Other]',
            'birthdate'      => 'permit_empty|valid_date[Y-m-d]',
            'address'        => 'permit_empty|max_length[255]',
            'bio'            => 'permit_empty|max_length[500]',
        ];

        $messages = [
            'email'          => ['is_unique' => 'That email address is already used by another account.'],
            'contact_number' => ['regex_match' => 'Contact number may only contain digits, spaces, +, - and parentheses.'],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()
                             ->with('error', implode(' ', $this->validator->getErrors()))
                             ->with('profile_tab', 'info');
        }

        $birthdate = trim((string) $this->request->getPost('birthdate'));
        if ($birthdate !== '' && $birthdate > date('Y-m-d')) {
            return redirect()->back()->withInput()
                             ->with('error', 'Birthdate cannot be in the future.')
                             ->with('profile_tab', 'info');
        }

        $data = [
            'email'          => trim((string) $this->request->getPost('email')),
            'contact_number' => $this->nullIfEmpty($this->request->getPost('contact_number')),
            'gender'         => $this->nullIfEmpty($this->request->getPost('gender')),
            'birthdate'      => $birthdate !== '' ? $birthdate : null,
            'address'        => $this->nullIfEmpty($this->request->getPost('address')),
            'bio'            => $this->nullIfEmpty($this->request->getPost('bio')),
        ];

        $this->users->update($user['id'], $data);
        session()->set('email', $data['email']);

        return redirect()->to($this->profileUrl())
                         ->with('success', 'Your profile has been updated.');
    }

    /* ---------------------------------------------------------
       PASSWORD
       --------------------------------------------------------- */

    public function password()
    {
        $user = $this->currentUser();
        if (! $user) {
            return redirect()->to(base_url('logout'));
        }

        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');
        $confirm = (string) $this->request->getPost('confirm_password');

        $fail = function (string $msg) {
            return redirect()->to($this->profileUrl())
                             ->with('error', $msg)
                             ->with('profile_tab', 'security');
        };

        if (! $this->users->verifyLogin($user['username'], $current)) {
            return $fail('Your current password is incorrect.');
        }
        if (strlen($new) < 8 || ! preg_match('/[A-Za-z]/', $new) || ! preg_match('/[0-9]/', $new)) {
            return $fail('New password must be at least 8 characters and contain both letters and numbers.');
        }
        if ($new !== $confirm) {
            return $fail('New password and confirmation do not match.');
        }
        if ($new === $current) {
            return $fail('New password must be different from your current password.');
        }

        $this->users->updateUser($user['id'], [
            'password'            => $new,
            'password_changed_at' => date('Y-m-d H:i:s'),
        ]);

        // A fresh session ID after a credential change
        session()->regenerate(true);

        return redirect()->to($this->profileUrl())
                         ->with('success', 'Your password has been changed.')
                         ->with('profile_tab', 'security');
    }

    /* ---------------------------------------------------------
       AVATAR
       --------------------------------------------------------- */

    public function avatar()
    {
        $user = $this->currentUser();
        if (! $user) {
            return redirect()->to(base_url('logout'));
        }

        $file = $this->request->getFile('avatar');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to($this->profileUrl())->with('error', 'Please choose an image to upload.');
        }

        $valid = $this->validate([
            'avatar' => 'uploaded[avatar]|is_image[avatar]|mime_in[avatar,image/jpeg,image/png,image/webp,image/gif]|max_size[avatar,2048]',
        ]);
        if (! $valid) {
            return redirect()->to($this->profileUrl())
                             ->with('error', 'Profile photo must be a JPEG, PNG, WEBP or GIF image no larger than 2 MB.');
        }

        $dir = FCPATH . self::AVATAR_DIR;
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            log_message('error', 'Avatar directory could not be created: ' . $dir);
            return redirect()->to($this->profileUrl())->with('error', 'Server could not prepare the upload directory.');
        }

        $newName = 'user_' . $user['id'] . '_' . $file->getRandomName();
        $file->move($dir, $newName);

        $this->deleteAvatarFile($user['avatar'] ?? null);

        $path = self::AVATAR_DIR . '/' . $newName;
        $this->users->update($user['id'], ['avatar' => $path]);
        session()->set('avatar', $path);

        return redirect()->to($this->profileUrl())->with('success', 'Profile photo updated.');
    }

    public function removeAvatar()
    {
        $user = $this->currentUser();
        if (! $user) {
            return redirect()->to(base_url('logout'));
        }

        $this->deleteAvatarFile($user['avatar'] ?? null);
        $this->users->update($user['id'], ['avatar' => null]);
        session()->remove('avatar');

        return redirect()->to($this->profileUrl())->with('success', 'Profile photo removed.');
    }

    /* ---------------------------------------------------------
       HELPERS
       --------------------------------------------------------- */

    private function currentUser(): ?array
    {
        $id = (int) session()->get('user_id');
        return $id ? $this->users->find($id) : null;
    }

    private function roleConfig(): array
    {
        return self::ROLES[session()->get('role')] ?? self::ROLES['admin'];
    }

    private function profileUrl(): string
    {
        return base_url($this->roleConfig()['prefix'] . '/profile');
    }

    private function nullIfEmpty($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    private function deleteAvatarFile(?string $path): void
    {
        // Only ever unlink files inside our own avatar directory
        if ($path && strpos($path, self::AVATAR_DIR . '/') === 0 && is_file(FCPATH . $path)) {
            @unlink(FCPATH . $path);
        }
    }

    /*
     * Seven KPIs for the profile, per role.
     *
     * Each KPI: label, value, icon, tone (colour), optional hint and
     * 'featured' (the first, wider card). Work is matched on the staff
     * member's user id; rows written before ids were recorded fall back
     * to the name they were stamped with.
     */
    private function activityStats(array $user): array
    {
        $db         = \Config\Database::connect();
        $id         = (int) $user['id'];
        $name       = (string) $user['full_name'];
        $monthStart = $db->escape(date('Y-m-01 00:00:00'));
        $todayStart = $db->escape(date('Y-m-d 00:00:00'));

        // "belongs to this user": by id, or by name on legacy rows without one
        $mine = static function ($builder, string $idCol, string $nameCol) use ($id, $name) {
            return $builder->groupStart()
                ->where($idCol, $id)
                ->orGroupStart()->where($idCol, null)->where($nameCol, $name)->groupEnd()
            ->groupEnd();
        };
        $peso   = static fn ($v) => '₱' . number_format((float) $v, 2);
        $plural = static fn (int $n, string $word) => number_format($n) . ' ' . $word . ($n === 1 ? '' : 's');
        $kpi    = static fn (string $label, $value, string $icon, string $tone, ?string $hint = null, bool $featured = false) =>
            compact('label', 'value', 'icon', 'tone', 'hint', 'featured');

        try {
            switch ($user['role']) {
                case 'radiologist':
                case 'med_tech':
                    // Same vocabulary as the worklists: "Pending" = paid but
                    // not touched by anyone yet (the department queue); the
                    // other figures are this person's own work.
                    $isXray = $user['role'] === 'radiologist';
                    $type   = $isXray ? DiagnosticRequestModel::TYPE_XRAY : DiagnosticRequestModel::TYPE_LAB;
                    $cols   = DiagnosticRequestModel::STAFF_COLUMNS[$type];

                    $builder = $db->table('diagnostic_requests')
                        ->select("COUNT(*) AS total,
                                  COALESCE(SUM(status = 'released'), 0) AS released,
                                  COALESCE(SUM(status = 'released' AND released_at >= {$monthStart}), 0) AS month,
                                  COALESCE(SUM(status IN ('in_progress','draft')), 0) AS working,
                                  COALESCE(SUM(status = 'completed'), 0) AS awaiting,
                                  AVG(CASE WHEN status = 'released' AND released_at > created_at
                                           THEN TIMESTAMPDIFF(MINUTE, created_at, released_at) END) AS tat", false)
                        ->where('type', $type)
                        ->whereIn('status', DiagnosticRequestModel::DEPARTMENT_STATUSES);
                    $row   = $mine($builder, $cols['id'], $cols['name'])->get()->getRowArray();
                    $queue = (new DiagnosticRequestModel())->getDepartmentCounts($type)['pending'];

                    return [
                        $kpi($isXray ? 'Examinations handled' : 'Lab requests handled', number_format((int) $row['total']),
                             $isXray ? 'bi-x-diamond' : 'bi-droplet-half', 'blue', 'All-time, credited to you', true),
                        $kpi('Results released',    number_format((int) $row['released']), 'bi-send-check',      'green',  'All-time'),
                        $kpi('Released this month', number_format((int) $row['month']),    'bi-calendar-check',  'teal',   date('F Y')),
                        $kpi($isXray ? 'In reading' : 'In progress', number_format((int) $row['working']), 'bi-hourglass-split', 'violet', 'Started by you'),
                        $kpi('Awaiting my release', number_format((int) $row['awaiting']), 'bi-clipboard-check', 'amber',  'Completed, not released'),
                        $kpi('Pending in queue',    number_format((int) $queue),           'bi-inbox',           'rose',   'Paid, no one started yet'),
                        $kpi('Avg. turnaround',     $this->formatDuration($row['tat']),    'bi-stopwatch',       'slate',  'Request to release'),
                    ];

                case 'admin':
                case 'receptionist':
                    $pay = $mine(
                        $db->table('payments')
                            ->select("COUNT(*) AS total,
                                      COALESCE(SUM(amount_paid), 0) AS amount,
                                      COALESCE(SUM(CASE WHEN payment_date >= {$monthStart} THEN amount_paid ELSE 0 END), 0) AS month,
                                      COALESCE(SUM(CASE WHEN payment_date >= {$todayStart} THEN amount_paid ELSE 0 END), 0) AS today,
                                      COALESCE(SUM(payment_date >= {$todayStart}), 0) AS today_n,
                                      AVG(amount_paid) AS average", false)
                            ->where('payment_status', \App\Models\PaymentModel::STATUS_PAID),
                        'received_by_id',
                        'received_by'
                    )->get()->getRowArray();

                    $inv = $mine(
                        $db->table('invoices')
                            ->select("COUNT(*) AS total,
                                      COALESCE(SUM(CASE WHEN status IN ('unpaid','partial') THEN balance ELSE 0 END), 0) AS outstanding,
                                      COALESCE(SUM(status IN ('unpaid','partial')), 0) AS open_n", false),
                        'created_by_id',
                        'created_by'
                    )->get()->getRowArray();

                    return [
                        $kpi('Amount collected',     $peso($pay['amount']), 'bi-wallet2',  'blue',  $plural((int) $pay['total'], 'payment') . ' received', true),
                        $kpi('Collected this month', $peso($pay['month']),  'bi-calendar-check', 'teal', date('F Y')),
                        $kpi('Collected today',      $peso($pay['today']),  'bi-sun',      'green', $plural((int) $pay['today_n'], 'payment')),
                        $kpi('Payments received',    number_format((int) $pay['total']), 'bi-cash-coin', 'violet', 'All-time'),
                        $kpi('Invoices created',     number_format((int) $inv['total']), 'bi-receipt',   'slate',  'All-time'),
                        $kpi('Outstanding balance',  $peso($inv['outstanding']), 'bi-exclamation-circle', 'rose', $plural((int) $inv['open_n'], 'open invoice') . ' you created'),
                        $kpi('Average payment',      $pay['average'] !== null ? $peso($pay['average']) : '—', 'bi-graph-up', 'amber', 'Per payment received'),
                    ];
            }
        } catch (\Throwable $e) {
            log_message('error', 'Profile stats failed: ' . $e->getMessage());
        }

        return [];
    }

    /** Minutes as "45m", "3h 20m" or "2d 4h"; "—" when there is no data. */
    private function formatDuration($minutes): string
    {
        if ($minutes === null || $minutes === '') {
            return '—';
        }
        $m = (int) round((float) $minutes);
        if ($m < 60) {
            return $m . 'm';
        }
        if ($m < 1440) {
            return intdiv($m, 60) . 'h' . ($m % 60 ? ' ' . ($m % 60) . 'm' : '');
        }
        $h = intdiv($m % 1440, 60);
        return intdiv($m, 1440) . 'd' . ($h ? ' ' . $h . 'h' : '');
    }
}
