-- Jalankan satu kali pada database aplikasi.
-- Menyimpan Telegram ID di log agar riwayat kick tetap lengkap setelah data member dihapus.
SET @column_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tb_member_logs'
    AND COLUMN_NAME = 'id_telegram'
);
SET @sql := IF(@column_exists = 0,
  'ALTER TABLE tb_member_logs ADD COLUMN id_telegram VARCHAR(50) NULL AFTER id_hfm',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
