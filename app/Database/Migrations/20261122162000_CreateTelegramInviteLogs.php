<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTelegramInviteLogs extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('telegram_invite_logs')) {
            return;
        }

        $this->db->query(<<<'SQL'
CREATE TABLE `telegram_invite_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_hfm` VARCHAR(50) NOT NULL,
  `telegram_id` VARCHAR(50) NOT NULL,
  `group_id` VARCHAR(100) NOT NULL,
  `invite_source` VARCHAR(30) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `message_id` BIGINT UNSIGNED DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME DEFAULT NULL,
  `delete_status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `deleted_at` DATETIME DEFAULT NULL,
  `delete_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `error_message` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_telegram_invite_user_time` (`telegram_id`, `sent_at`),
  KEY `idx_telegram_invite_cleanup` (`delete_status`, `expires_at`),
  KEY `idx_telegram_invite_hfm` (`id_hfm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down()
    {
        $this->forge->dropTable('telegram_invite_logs', true);
    }
}
