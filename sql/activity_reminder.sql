-- Log idempotensi pengingat aktivitas member.
-- Jalankan sekali pada database yang sedang digunakan aplikasi.
CREATE TABLE IF NOT EXISTS `activity_reminder_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT NOT NULL,
  `phase` ENUM('h3','h1','final') NOT NULL,
  `telegram_id` VARCHAR(50) NOT NULL,
  `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_reminder_member_phase` (`member_id`, `phase`),
  KEY `idx_activity_reminder_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Kolom last_trade pada database aktual nullable; jangan simpan tanggal semu.
UPDATE `tb_member_vip`
SET `last_trade` = NULL
WHERE `last_trade` IN ('0000-00-00 00:00:00', '1970-01-01 00:00:00');
