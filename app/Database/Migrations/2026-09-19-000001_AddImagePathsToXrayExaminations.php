<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddImagePathsToXrayExaminations extends Migration
{
    public function up()
    {
        $this->forge->addColumn('xray_examinations', [
            'image_paths' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'image_path',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('xray_examinations', 'image_paths');
    }
}