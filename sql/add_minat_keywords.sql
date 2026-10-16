-- Perbaikan keyword respons awal bot.
-- Jalankan pada database bot yang aktif setelah melakukan backup.
-- Tidak ada perubahan struktur tabel.

SET NAMES utf8mb4;
START TRANSACTION;

-- Tambahkan variasi jawaban minat ke flow tahap pertama tanpa duplikasi.
UPDATE bot_flows
SET trigger_keywords = CONCAT(
    TRIM(BOTH ',' FROM trigger_keywords),
    ', minat, berminat, tertarik, mau gabung, mau ikut, ikut'
)
WHERE step_level = 1
  AND trigger_keywords NOT LIKE '%minat%';

UPDATE bot_flows
SET trigger_keywords = CONCAT(
    TRIM(BOTH ',' FROM trigger_keywords),
    ', yaa, iyah, yap, yes'
)
WHERE step_level = 1
  AND trigger_keywords NOT LIKE '%yaa%';

COMMIT;
