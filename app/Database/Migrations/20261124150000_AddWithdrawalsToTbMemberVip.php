<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWithdrawalsToTbMemberVip extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('tb_member_vip')) {
            return;
        }

        if (!$this->db->fieldExists('withdrawals', 'tb_member_vip')) {
            $this->forge->addColumn('tb_member_vip', [
                'withdrawals' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '15,2',
                    'null'       => true,
                    'after'      => 'deposit',
                ],
            ]);
        }
    }

    public function down()
    {
        if (
            $this->db->tableExists('tb_member_vip')
            && $this->db->fieldExists('withdrawals', 'tb_member_vip')
        ) {
            $this->forge->dropColumn('tb_member_vip', 'withdrawals');
        }
    }
}
