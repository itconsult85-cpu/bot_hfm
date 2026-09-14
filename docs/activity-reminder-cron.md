# Pengingat Aktivitas Member

Jalankan SQL [`sql/activity_reminder.sql`](../sql/activity_reminder.sql) satu kali pada database yang sedang digunakan aplikasi. Tabel tersebut menyimpan log per member dan fase agar cron yang berjalan ulang tidak mengirim pesan ganda.

Jadwal reminder client pukul 08:00 WIB dijalankan oleh `bot.js`, bukan oleh laporan harian pukul 04:05 WIB. Blok laporan 04:05 di `bot.js` tetap terpisah dan tidak diubah.

Tambahkan scheduler 08:00 berikut pada proses `bot.js` yang sedang berjalan:

```js
cron.schedule('0 8 * * *', async () => {
  await axios.post('http://103.89.4.144/bot_wa/client/run-activity-reminders', {}, { timeout: 120000 });
}, { scheduled: true, timezone: 'Asia/Jakarta' });
```

Endpoint tersebut hanya mengirim reminder client yang jatuh tempo berdasarkan status tidak aktif trading dan langsung mengirim laporan hasilnya ke admin. Jika ingin memakai cron server sebagai alternatif, gunakan konfigurasi berikut dengan timezone `Asia/Jakarta`:

```cron
5 4 * * * cd /path/ke/project && php spark hfm:sync-members >> writable/logs/hfm-sync.log 2>&1
0 8 * * * cd /path/ke/project && php spark bot:send-activity-reminders >> writable/logs/activity-reminders.log 2>&1
```

Command `hfm:sync-members` mengambil `last_trade`, nama, dan currency terbaru dari API HFM untuk seluruh member. Jika API tidak mengirim tanggal trading yang valid, database menyimpan `NULL`, bukan `0000-00-00 00:00:00`. Fitur sync manual yang sudah ada tetap tersedia.

Sistem mengirimkan fase yang aktif dan dikonfigurasi admin berdasarkan `days_after_join`. Karena fase disimpan di tabel, admin dapat memakai dua fase, tiga fase, atau lebih tanpa perubahan kode.

Admin dapat membuka `/activity-reminders` untuk melihat kandidat hari berjalan. Data status trading diambil langsung dari API HFM. Tidak ada kick atau delete otomatis. Jika admin menekan tombol **Kick & Hapus**, barulah aplikasi mencatat aktivitas, meminta bot mengeluarkan member dari grup melalui endpoint bot yang sudah digunakan aplikasi, lalu menghapus data dari `tb_member_vip`.
