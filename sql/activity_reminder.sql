-- Tabel log idempotensi pengiriman.
CREATE TABLE IF NOT EXISTS `activity_reminder_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `member_id` INT NOT NULL,
  `phase` VARCHAR(50) NOT NULL,
  `telegram_id` VARCHAR(50) NOT NULL,
  `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_reminder_member_phase` (`member_id`, `phase`),
  KEY `idx_activity_reminder_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Konfigurasi fase/pesan yang dapat dikelola admin.
CREATE TABLE IF NOT EXISTS `activity_reminder_phases` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phase_key` VARCHAR(50) NOT NULL,
  `phase_name` VARCHAR(100) NOT NULL,
  `days_after_join` INT UNSIGNED NOT NULL,
  `message` TEXT NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_activity_reminder_phase_key` (`phase_key`),
  UNIQUE KEY `uq_activity_reminder_days` (`days_after_join`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `activity_reminder_phases` (`phase_key`, `phase_name`, `days_after_join`, `message`) VALUES
('h3', 'H-3 Reminder', 7, '📢 REMINDER AKTIVITAS MEMBER\n\nHalo teman-teman BO$$CUAN 👋\n\nKami ingin mengingatkan bahwa status keanggotaan di grup diperuntukkan bagi member yang aktif melakukan transaksi dan mengikuti aktivitas komunitas.\n\nBagi yang beberapa waktu terakhir belum melakukan transaksi, mohon untuk mulai kembali aktif. 🙏\n\n⏰ 3 hari ke depan akan dilakukan evaluasi aktivitas member.\n\nBagi member yang tetap tidak melakukan transaksi, akan kami hubungi kembali sebelum dilakukan penertiban grup.\n\nTerima kasih atas pengertiannya.\n🔥 Aktif bersama, cuan bersama!\n\nBO$$CUAN'),
('h1', 'H-1 Final Reminder', 9, '⚠️ FINAL REMINDER MEMBER\n\nHalo member BO$$CUAN 👋\n\nKami mengingatkan kembali bahwa besok akan dilakukan evaluasi aktivitas member.\n\nBagi member yang belum melakukan transaksi/aktivitas, mohon segera kembali aktif agar status keanggotaan tetap dipertahankan.\n\n❗️ Member yang sampai batas waktu evaluasi belum melakukan transaksi akan masuk daftar penertiban dan dapat dikeluarkan dari grup.\n\nJika memang sedang memiliki kendala, silakan hubungi admin.\n\nTerima kasih atas perhatian dan kerja samanya. 🙏\n\n🔥 Jangan sampai kehilangan akses ke komunitas BO$$CUAN.'),
('final', 'Hari Evaluasi', 10, '🚨 PEMBERITAHUAN TERAKHIR\n\nHalo teman-teman 👋\n\nHari ini kami melakukan evaluasi dan penertiban member berdasarkan aktivitas transaksi di komunitas.\n\nBagi member yang sampai batas waktu yang telah ditentukan belum melakukan transaksi/aktivitas, sesuai ketentuan akan dilakukan pengeluaran dari grup.\n\nMohon dipahami bahwa langkah ini dilakukan untuk menjaga grup tetap aktif dan diisi oleh member yang benar-benar mengikuti aktivitas BO$$CUAN.\n\n🙏 Terima kasih atas kebersamaan dan pengertiannya.\n\n🔥 BO$$CUAN — Aktif, Disiplin, Cuan Bersama!')
ON DUPLICATE KEY UPDATE `phase_name` = VALUES(`phase_name`);

UPDATE `tb_member_vip` SET `last_trade` = NULL WHERE `last_trade` IN ('0000-00-00 00:00:00', '1970-01-01 00:00:00');
