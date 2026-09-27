-- Metadata sumber pendaftaran dari deep-link tombol WhatsApp Telegram.
-- Aman dijalankan setelah backup; tidak menghapus atau mengubah baris lama.
ALTER TABLE `tb_member_vip`
  ADD COLUMN `registration_source` VARCHAR(50) NULL DEFAULT NULL AFTER `id_telegram`,
  ADD COLUMN `registration_source_id` INT UNSIGNED NULL DEFAULT NULL AFTER `registration_source`,
  ADD KEY `idx_tb_member_vip_registration_source` (`registration_source`, `registration_source_id`);
