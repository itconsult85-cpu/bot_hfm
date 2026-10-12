-- Rekonsiliasi satu kali berdasarkan dump db_hfm_bot (3).sql.
-- Hitungan terverifikasi pada dump:
--   tb_member_vip berstatus lepas_ib                         : 188 record
--   record tersebut dengan log terbaru bertipe kick/admin  : 188 record
-- Skrip hanya memproses roster jika jumlah kandidat tepat 188.
-- Baris/event tb_member_logs tetap dipertahankan; ID Telegram yang kosong
-- pada log terkait diisi dari roster agar statistik unik tidak kehilangan identitas.
-- Jalankan setelah memilih database aplikasi yang benar.

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_reconcile_kicked_lepas_ib;
CREATE TEMPORARY TABLE tmp_reconcile_kicked_lepas_ib (
    id_hfm VARCHAR(64) NOT NULL PRIMARY KEY,
    id_telegram VARCHAR(64) NOT NULL
) ENGINE=MEMORY;

INSERT INTO tmp_reconcile_kicked_lepas_ib (id_hfm, id_telegram)
SELECT DISTINCT vip.id_hfm, vip.id_telegram
FROM tb_member_vip AS vip
WHERE vip.status = 'lepas_ib'
  AND vip.id_hfm IS NOT NULL
  AND vip.id_telegram IS NOT NULL
  AND TRIM(vip.id_telegram) NOT IN ('', '-')
  AND EXISTS (
      SELECT 1
      FROM tb_member_logs AS kick_log
      WHERE kick_log.id_hfm = vip.id_hfm
        AND kick_log.tipe_aktivitas IN ('dihapus_admin_web', 'keluar_di_remove')
        AND NOT EXISTS (
            SELECT 1
            FROM tb_member_logs AS newer_log
            WHERE newer_log.id_hfm = kick_log.id_hfm
              AND newer_log.id > kick_log.id
        )
  );

-- Harus menunjukkan 188 sebelum DELETE. Jika tidak, DELETE di bawah akan menghapus 0 baris.
SELECT COUNT(*) AS matched_roster_records_before_delete
FROM tmp_reconcile_kicked_lepas_ib;

UPDATE tb_member_logs AS log_entry
INNER JOIN tmp_reconcile_kicked_lepas_ib AS candidate
    ON candidate.id_hfm = log_entry.id_hfm
SET log_entry.id_telegram = candidate.id_telegram
WHERE (log_entry.id_telegram IS NULL OR TRIM(log_entry.id_telegram) IN ('', '-'))
  AND (SELECT COUNT(*) FROM tmp_reconcile_kicked_lepas_ib) = 188;

SET @backfilled_log_rows = ROW_COUNT();

DELETE vip
FROM tb_member_vip AS vip
INNER JOIN tmp_reconcile_kicked_lepas_ib AS candidate
    ON candidate.id_hfm = vip.id_hfm
WHERE vip.status = 'lepas_ib'
  AND (SELECT COUNT(*) FROM tmp_reconcile_kicked_lepas_ib) = 188;

SET @reconciled_roster_rows = ROW_COUNT();

DROP TEMPORARY TABLE tmp_reconcile_kicked_lepas_ib;
COMMIT;

SELECT @reconciled_roster_rows AS deleted_roster_records,
       @backfilled_log_rows AS log_rows_linked_to_telegram,
       'tidak ada event tb_member_logs yang dihapus' AS activity_history;
