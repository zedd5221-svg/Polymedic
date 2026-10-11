<?php

namespace App\Models;

use CodeIgniter\Model;

class InvoiceItemModel extends Model
{
    protected $table      = 'invoice_items';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'invoice_id',
        'service_name',
        'category',
        'qty',
        'unit_price',
        'line_total',
        'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * All items on a given invoice, in insertion order.
     */
    public function getForInvoice(int $invoiceId): array
    {
        return $this->where('invoice_id', $invoiceId)
                    ->orderBy('id', 'ASC')
                    ->findAll();
    }

    /**
     * Replace the entire item set on an invoice. Called after the
     * admin edits the line items on the "create invoice" screen.
     */
    public function replaceForInvoice(int $invoiceId, array $items): void
    {
        $this->where('invoice_id', $invoiceId)->delete();

        if (empty($items)) {
            return;
        }

        $rows = [];
        foreach ($items as $item) {
            $name  = trim((string) ($item['service_name'] ?? $item['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $qty   = (float) ($item['qty'] ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }

            $price = (float) ($item['unit_price'] ?? $item['charge'] ?? 0);
            $line  = round($qty * $price, 2);

            $rows[] = [
                'invoice_id'   => $invoiceId,
                'service_name' => $name,
                'category'     => $item['category'] ?? null,
                'qty'          => number_format($qty, 2, '.', ''),
                'unit_price'   => number_format($price, 2, '.', ''),
                'line_total'   => number_format($line, 2, '.', ''),
                'notes'        => $item['notes'] ?? null,
                'created_at'   => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($rows)) {
            $this->insertBatch($rows);
        }
    }
}