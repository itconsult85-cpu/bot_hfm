-- Template welcome Telegram yang dapat diedit dari menu Pengaturan Bot > Variabel Global.
-- Aman dijalankan satu kali setelah backup database.
-- Tidak mengubah struktur tabel dan tidak menimpa nilai yang sudah diedit admin.

INSERT INTO `bot_globals` (`key_name`, `key_value`)
SELECT 'WELCOME_MESSAGE', 'Selamat Datang {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.'
WHERE NOT EXISTS (
  SELECT 1 FROM `bot_globals` WHERE `key_name` = 'WELCOME_MESSAGE'
);

INSERT INTO `bot_globals` (`key_name`, `key_value`)
SELECT 'WELCOME_BACK_MESSAGE', 'Selamat Datang Kembali {NAMA_USER}, pastikan membaca rules yang ada di dalam group, pahami dan lakukan.'
WHERE NOT EXISTS (
  SELECT 1 FROM `bot_globals` WHERE `key_name` = 'WELCOME_BACK_MESSAGE'
);

SELECT `key_name`, `key_value`
FROM `bot_globals`
WHERE `key_name` IN ('WELCOME_MESSAGE', 'WELCOME_BACK_MESSAGE');
