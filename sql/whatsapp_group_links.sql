-- CRUD link grup Telegram/WhatsApp dan pencatatan event join WhatsApp.
-- Jalankan satu kali pada database aktif setelah backup.

CREATE TABLE IF NOT EXISTS `bot_group_links` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Migrasikan ID_GRUP_VIP lama jika tersedia. Link undangan dapat diisi dari dashboard.
INSERT INTO `bot_group_links` (`platform`, `group_name`, `invite_link`, `group_id`, `is_active`)
SELECT 'telegram', 'Grup VIP Telegram', '', TRIM(g.`key_value`), 1
FROM `bot_globals` g
WHERE g.`key_name` = 'ID_GRUP_VIP'
  AND TRIM(g.`key_value`) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM `bot_group_links` b WHERE b.`platform` = 'telegram'
  );

CREATE TABLE IF NOT EXISTS `whatsapp_group_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `whatsapp_id` VARCHAR(100) NOT NULL,
  `phone_number` VARCHAR(30) DEFAULT NULL,
  `display_name` VARCHAR(150) DEFAULT NULL,
  `group_id` VARCHAR(100) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `joined_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `left_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_whatsapp_group_member_identity` (`whatsapp_id`, `group_id`),
  KEY `idx_whatsapp_group_member_phone` (`phone_number`),
  KEY `idx_whatsapp_group_member_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Setelah migrasi, buat satu baris Telegram dan satu baris WhatsApp melalui menu dashboard.
