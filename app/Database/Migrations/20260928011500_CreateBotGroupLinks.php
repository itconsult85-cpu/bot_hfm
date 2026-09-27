<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBotGroupLinks extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('bot_group_links')) {
            return;
        }

        $this->db->query(<<<'SQL'
CREATE TABLE `bot_group_links` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `platform` VARCHAR(20) NOT NULL,
  `group_name` VARCHAR(150) NOT NULL,
  `invite_link` VARCHAR(500) NOT NULL,
  `group_id` VARCHAR(100) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bot_group_links_platform` (`platform`),
  KEY `idx_bot_group_links_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
SQL);
    }

    public function down()
    {
        $this->forge->dropTable('bot_group_links', true);
    }
}
