-- Sinkronisasi konfigurasi grup Telegram lama ke bot_group_links.
-- Aman dijalankan setelah backup database.
-- Tidak menghapus data dan tidak mengubah bot_globals.

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

-- Jika baris Telegram sudah ada, samakan group_id dengan konfigurasi lama.
-- invite_link dan group_name yang sudah diatur tidak ditimpa.
UPDATE `bot_group_links` AS b
INNER JOIN `bot_globals` AS g
  ON g.`key_name` = 'ID_GRUP_VIP'
SET b.`group_id` = TRIM(g.`key_value`),
    b.`is_active` = 1,
    b.`updated_at` = CURRENT_TIMESTAMP
WHERE b.`platform` = 'telegram'
  AND TRIM(g.`key_value`) <> '';

-- Jika baris Telegram belum ada, buat dari ID_GRUP_VIP lama.
INSERT INTO `bot_group_links`
  (`platform`, `group_name`, `invite_link`, `group_id`, `is_active`)
SELECT
  'telegram',
  'Grup VIP Telegram',
  '',
  TRIM(g.`key_value`),
  1
FROM `bot_globals` AS g
WHERE g.`key_name` = 'ID_GRUP_VIP'
  AND TRIM(g.`key_value`) <> ''
  AND NOT EXISTS (
    SELECT 1
    FROM `bot_group_links` AS b
    WHERE b.`platform` = 'telegram'
  );

-- Verifikasi hasil sinkronisasi.
SELECT `platform`, `group_name`, `group_id`, `is_active`
FROM `bot_group_links`
WHERE `platform` = 'telegram';
