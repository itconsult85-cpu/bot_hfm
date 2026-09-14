-- Jalankan satu kali pada database aplikasi.
CREATE TABLE IF NOT EXISTS `bot_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_key` VARCHAR(50) NOT NULL,
  `schedule_name` VARCHAR(150) NOT NULL,
  `schedule_type` VARCHAR(30) NOT NULL,
  `run_at` CHAR(5) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bot_schedule_key` (`schedule_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `bot_schedules` (`schedule_key`, `schedule_name`, `schedule_type`, `run_at`, `is_active`)
SELECT 'daily_report', 'Laporan harian database', 'report', '04:05', 1
WHERE NOT EXISTS (SELECT 1 FROM `bot_schedules` WHERE `schedule_key` = 'daily_report');

INSERT INTO `bot_schedules` (`schedule_key`, `schedule_name`, `schedule_type`, `run_at`, `is_active`)
SELECT 'activity_reminder', 'Evaluasi dan reminder aktivitas', 'activity', '08:00', 1
WHERE NOT EXISTS (SELECT 1 FROM `bot_schedules` WHERE `schedule_key` = 'activity_reminder');

-- Buat token acak panjang dan simpan di bot_globals.
-- Nilai yang sama harus tersedia untuk bot.js dan dashboard.
INSERT INTO `bot_globals` (`key_name`, `key_value`)
SELECT 'BOT_CONTROL_TOKEN', 'GANTI_DENGAN_TOKEN_ACAK_PANJANG'
WHERE NOT EXISTS (SELECT 1 FROM `bot_globals` WHERE `key_name` = 'BOT_CONTROL_TOKEN');
