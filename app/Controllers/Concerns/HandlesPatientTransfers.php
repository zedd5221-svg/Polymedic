<?php

namespace App\Controllers\Concerns;

use App\Models\PatientTransferModel;
use App\Models\PaymentModel;

/*
 * Same-role patient handoffs, shared by the MedTech and Radiologist
 * controllers. Each controller says which request type it reads and
 * where its worklist lives; the rules are in PatientTransferModel.
 *
 * Responses are JSON for AJAX callers (the notification bell) and a
 * redirect with a flash message otherwise (forms on the pages).
 */
trait HandlesPatientTransfers
{
    /** Request type this department reads: 'lab' or 'xray'. */
    abstract protected function transferType(): string;

    /** Route prefix of this department, e.g. 'medtech'. */
    abstract protected function transferBase(): string;

    /** Worklist the transfer panel sits on, e.g. 'medtech/requests'. */
    abstract protected function transferHome(): string;

    /** Data for partials/transfer_panel on the worklist. */
    protected function transferPanelData(): array
    {
        $model  = new PatientTransferModel();
        $userId = (int) PaymentModel::currentStaffId();
        $type   = $this->transferType();

        return [
            'base'       => $this->transferBase(),
            'roleLabel'  => $type === 'xray' ? 'radiologist' : 'med tech',
            'patients'   => $model->transferableRequests($userId, $type),
            'recipients' => $model->recipients($userId, $type),
            'incoming'   => $model->pendingFor($userId, $type, 'incoming'),
            'outgoing'   => $model->pendingFor($userId, $type, 'outgoing'),
        ];
    }

    public function createTransfer()
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $result = (new PatientTransferModel())->createTransfer(
            $this->transferActor(),
            (int) $this->request->getPost('to_user_id'),
            $this->transferType(),
            (array) ($this->request->getPost('request_ids') ?? []),
            (string) $this->request->getPost('reason')
        );

        return $this->transferResponse($result);
    }

    public function respondTransfer($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        $action = (string) $this->request->getPost('action');
        if (! in_array($action, ['accept', 'decline'], true)) {
            return $this->transferResponse(['success' => false, 'message' => 'Choose Accept or Decline.']);
        }

        try {
            $result = (new PatientTransferModel())->respond((int) $id, $this->transferActor(), $action === 'accept');
        } catch (\Throwable $e) {
            log_message('error', 'Patient transfer respond failed: ' . $e->getMessage());
            $result = ['success' => false, 'message' => 'The transfer could not be completed. Please try again.'];
        }

        return $this->transferResponse($result);
    }

    public function cancelTransfer($id)
    {
        $redirect = $this->checkAuth();
        if ($redirect) return $redirect;

        return $this->transferResponse((new PatientTransferModel())->cancelTransfer((int) $id, $this->transferActor()));
    }

    /** @return array{id: int, name: string} */
    private function transferActor(): array
    {
        return ['id' => (int) PaymentModel::currentStaffId(), 'name' => PaymentModel::currentCashierName()];
    }

    private function transferResponse(array $result)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON($result);
        }
        return redirect()->to(base_url($this->transferHome()) . '#transfers')
                         ->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
