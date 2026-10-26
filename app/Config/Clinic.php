<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/*
 * Clinic identity printed on receipts and request slips.
 * Change it here once instead of in every print template.
 */
class Clinic extends BaseConfig
{
    public string $name    = 'POLYMEDIC DIAGNOSTIC CENTER';
    public string $tagline = 'Clinical Laboratory & Radiology Services';
    public string $address = 'Quezon Avenue, Kidapawan City, Cotabato 9400';
    public string $phone   = '(064) 123-4567';
    public string $email   = 'info@polymedic.ph';
    public string $tin     = '000-000-000-000';

    public function toArray(): array
    {
        return [
            'name'    => $this->name,
            'tagline' => $this->tagline,
            'address' => $this->address,
            'phone'   => $this->phone,
            'email'   => $this->email,
            'tin'     => $this->tin,
        ];
    }
}
