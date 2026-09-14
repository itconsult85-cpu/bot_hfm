# Pengingat Aktivitas Member

Jalankan SQL [`sql/activity_reminder.sql`](../sql/activity_reminder.sql) satu kali pada database yang sedang digunakan aplikasi. Tabel tersebut menyimpan log per member dan fase agar cron yang berjalan ulang tidak mengirim pesan ganda.

Jadwal pengiriman sekarang dikelola dari menu **Pengaturan Bot → Jadwal Pengiriman & Bot** di dashboard. Nilai awal tetap laporan harian pukul **04:05 WIB** dan reminder aktivitas pukul **08:00 WIB**. Blok laporan 04:05 tetap terpisah dari evaluasi aktivitas.

Bot membaca perubahan jadwal dari database secara otomatis setiap menit. Endpoint tersebut mengirim reminder client sesuai fase yang jatuh tempo berdasarkan status tidak aktif trading. Setelah itu admin menerima report yang berisi jumlah client pada fase **H-3**, **H-1**, dan **Final**, serta link **KICK & HAPUS** untuk setiap client. Link tersebut tidak mengeksekusi apa pun sebelum admin membukanya; saat dibuka, client dikick dari Telegram lalu datanya dihapus dari database.

```cron
5 4 * * * cd /path/ke/project && php spark hfm:sync-members >> writable/logs/hfm-sync.log 2>&1
0 8 * * * cd /path/ke/project && php spark bot:send-activity-reminders >> writable/logs/activity-reminders.log 2>&1
```

Command `hfm:sync-members` mengambil `last_trade`, nama, dan currency terbaru dari API HFM untuk seluruh member. Jika API tidak mengirim tanggal trading yang valid, database menyimpan `NULL`, bukan `0000-00-00 00:00:00`. Fitur sync manual yang sudah ada tetap tersedia.

Sistem mengirimkan fase yang aktif dan dikonfigurasi admin berdasarkan `days_after_join`. Karena fase disimpan di tabel, admin dapat memakai dua fase, tiga fase, atau lebih tanpa perubahan kode.

Admin dapat membuka `/activity-reminders` untuk melihat kandidat hari berjalan. Data status trading diambil langsung dari API HFM. Tidak ada kick atau delete otomatis. Jika admin menekan tombol **Kick & Hapus**, barulah aplikasi mencatat aktivitas, meminta bot mengeluarkan member dari grup melalui endpoint bot yang sudah digunakan aplikasi, lalu menghapus data dari `tb_member_vip`.
