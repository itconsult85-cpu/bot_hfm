<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MigrateTelegramGroupFromGlobals extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('bot_group_links') || !$this->db->tableExists('bot_globals')) {
            return;
        }

        $exists = $this->db->table('bot_group_links')
            ->where('platform', 'telegram')
            ->countAllResults();
        if ($exists > 0) {
            return;
        }

        $legacy = $this->db->table('bot_globals')
            ->where('key_name', 'ID_GRUP_VIP')
            ->get()
            ->getRowArray();
        if (!$legacy || trim((string) ($legacy['key_value'] ?? '')) === '') {
            return;
        }

        // Link undangan dikosongkan karena ID_GRUP_VIP lama hanya menyimpan chat ID.
        // Admin dapat mengisi link undangan yang benar dari menu CRUD link grup.
        $this->db->table('bot_group_links')->insert([
            'platform' => 'telegram',
            'group_name' => 'Grup VIP Telegram',
            'invite_link' => '',
            'group_id' => trim((string) $legacy['key_value']),
            'is_active' => 1,
        ]);
    }

    public function down()
    {
        $this->db->table('bot_group_links')
            ->where('platform', 'telegram')
            ->where('group_name', 'Grup VIP Telegram')
            ->delete();
    }
}
