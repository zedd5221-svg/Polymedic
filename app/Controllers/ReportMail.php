<?php

namespace App\Controllers;

/*
 * Emails the PDF soft copy of a report (partials/pdf_tools.php).
 *
 * The browser makes the PDF from the printable page and posts it here
 * with the recipient, subject and message. Only logged-in staff can
 * send, the attachment must be a real PDF of at most 10 MB, and every
 * send is logged with who sent what to whom.
 *
 * Mail settings come from .env (email.protocol, email.SMTPHost, ...);
 * until they are filled in, sending says so instead of failing quietly.
 */
class ReportMail extends BaseController
{
    private const STAFF_ROLES = ['admin', 'receptionist', 'med_tech', 'technologist', 'radiologist'];
    private const MAX_BYTES   = 10 * 1024 * 1024;

    public function send()
    {
        if (! session()->get('is_logged_in') || ! in_array(session()->get('role'), self::STAFF_ROLES, true)) {
            return $this->reply(false, 'Your session has ended. Sign in again, then resend.', 401);
        }

        $to      = trim((string) $this->request->getPost('to'));
        $subject = trim(preg_replace('/[\r\n]+/', ' ', (string) $this->request->getPost('subject')));
        $message = trim((string) $this->request->getPost('message'));
        $file    = $this->request->getFile('pdf');

        if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->reply(false, 'Enter a valid email address.');
        }
        if ($subject === '' || mb_strlen($subject) > 150) {
            return $this->reply(false, 'Enter a subject of up to 150 characters.');
        }
        if (mb_strlen($message) > 2000) {
            return $this->reply(false, 'Keep the message under 2000 characters.');
        }
        if ($file === null || ! $file->isValid()) {
            return $this->reply(false, 'The PDF did not arrive. Please try again.');
        }
        if ($file->getSize() > self::MAX_BYTES) {
            return $this->reply(false, 'This PDF is larger than 10 MB, too big to email. Download it instead.');
        }
        $head = (string) file_get_contents($file->getTempName(), false, null, 0, 5);
        if ($head !== '%PDF-') {
            return $this->reply(false, 'Only PDF files can be sent.');
        }

        $config = config('Email');
        if (trim($config->fromEmail) === '' || ($config->protocol === 'smtp' && trim($config->SMTPHost) === '')) {
            return $this->reply(false, 'Email is not set up yet. Ask the administrator to add the clinic\'s mail settings (email.* in .env).');
        }

        $filename = preg_replace('/[^\w\- .()]+/u', '', $file->getClientName()) ?: 'report.pdf';
        if (! str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        $clinic = config('Clinic');
        $email  = service('email');
        $email->setFrom($config->fromEmail, $config->fromName !== '' ? $config->fromName : $clinic->name);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMailType('html');
        $email->setMessage(
            '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5;color:#0f172a">'
            . nl2br(esc($message !== '' ? $message : 'Please find the attached document.'))
            . '<hr style="border:0;border-top:1px solid #e2e8f0;margin:20px 0">'
            . '<p style="font-size:12px;color:#64748b">' . esc($clinic->name) . ' · ' . esc($clinic->address) . ' · ' . esc($clinic->phone) . '</p>'
            . '</div>'
        );
        // With a MIME type, attach() takes the file's contents, not its path.
        $email->attach((string) file_get_contents($file->getTempName()), 'attachment', $filename, 'application/pdf');

        $sender = trim((string) (session()->get('full_name') ?? session()->get('username') ?? ''));

        if (! $email->send(false)) {
            log_message('error', "Report email failed: {$filename} to {$to} by {$sender}. " . $email->printDebugger(['headers']));
            return $this->reply(false, 'The email could not be sent. Check the mail settings or try again later.');
        }

        log_message('info', "Report emailed: {$filename} to {$to} by {$sender}.");
        return $this->reply(true, 'Sent to ' . $to . '.');
    }

    private function reply(bool $success, string $message, int $status = 200)
    {
        return $this->response->setStatusCode($status)->setJSON(['success' => $success, 'message' => $message]);
    }
}
