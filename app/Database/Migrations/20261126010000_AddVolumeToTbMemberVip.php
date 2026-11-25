<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddVolumeToTbMemberVip extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('volume', 'tb_member_vip')) {
            $this->forge->addColumn('tb_member_vip', [
                'volume' => [
                    'type' => 'DECIMAL',
                    'constraint' => '20,2',
                    'null' => true,
                    'default' => null,
                    'after' => 'deposit',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('volume', 'tb_member_vip')) {
            $this->forge->dropColumn('tb_member_vip', 'volume');
        }
    }
}
