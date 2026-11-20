<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTelegramUsernameToGroupMembers extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('telegram_group_members')) {
            return;
        }

        if (!$this->db->fieldExists('telegram_username', 'telegram_group_members')) {
            $this->forge->addColumn('telegram_group_members', [
                'telegram_username' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'null'       => true,
                    'after'      => 'telegram_id',
                ],
            ]);
        }
    }

    public function down()
    {
        if (
            $this->db->tableExists('telegram_group_members')
            && $this->db->fieldExists('telegram_username', 'telegram_group_members')
        ) {
            $this->forge->dropColumn('telegram_group_members', 'telegram_username');
        }
    }
}
