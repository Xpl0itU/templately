<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddImageSizesToFilledFiles extends Migration
{
    public function up()
    {
        $this->forge->addColumn('filledFiles', [
            'imageSizes' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON data storing image size settings for each image field'
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('filledFiles', 'imageSizes');
    }
}
