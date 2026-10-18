-- Menambahkan alias 'wd' ke FAQ withdrawal.
-- Tidak mengubah struktur tabel dan tidak menghapus data.
-- Jalankan hanya jika FAQ withdrawal memang memiliki keyword withdraw.

UPDATE bot_faqs
SET keywords = CONCAT(TRIM(BOTH ',' FROM keywords), ', wd')
WHERE LOWER(keywords) LIKE '%withdraw%'
  AND FIND_IN_SET('wd', LOWER(REPLACE(keywords, ' ', ''))) = 0;
