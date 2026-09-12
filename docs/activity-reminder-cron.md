# Pengingat Aktivitas Member

Jalankan SQL [`sql/activity_reminder.sql`](../sql/activity_reminder.sql) satu kali pada database yang sedang digunakan aplikasi. Tabel tersebut menyimpan log per member dan fase agar cron yang berjalan ulang tidak mengirim pesan ganda.

Tambahkan cron server berikut dengan timezone `Asia/Jakarta` atau set timezone PHP/server ke `Asia/Jakarta`:

```cron
0 8 * * * cd /path/ke/project && php spark bot:send-activity-reminders >> writable/logs/activity-reminders.log 2>&1
```

Sistem mengirimkan fase H-3 pada hari ke-7 setelah `created_at`, H-1 pada hari ke-9, dan pemberitahuan terakhir pada hari ke-10. Kandidat hanya member dengan `status = aktif`, `id_telegram` valid, serta `last_trade` kosong atau tidak lebih baru daripada waktu bergabung.

Admin dapat membuka `/activity-reminders` untuk melihat kandidat hari berjalan. Tombol **Keluarkan & Hapus** mencatat aktivitas, meminta bot mengeluarkan member dari grup melalui endpoint bot yang sudah digunakan aplikasi, lalu menghapus data dari `tb_member_vip`.
