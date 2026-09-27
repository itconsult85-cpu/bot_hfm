# Link Grup Telegram & WhatsApp Dinamis

## Fungsi

Menu **Link Grup Tele & WhatsApp** mengelola link undangan dan ID grup yang dipakai bot tanpa mengubah source code.

- Bot Telegram membaca link Telegram dan WhatsApp saat mengirim akses VIP.
- Bot WhatsApp membaca link WhatsApp dari dashboard dan tetap memakai link tersebut setelah restart.
- Jika link diubah, cache bot disegarkan maksimal sekitar 60 detik.
- ID grup dipakai untuk mendeteksi event join/leave dan menjalankan kick.

## Instalasi database

1. Backup database terlebih dahulu.
2. Jalankan file [`sql/whatsapp_group_links.sql`](../sql/whatsapp_group_links.sql) satu kali.
3. Pastikan tabel `bot_group_links` dan `whatsapp_group_members` berhasil dibuat.

Bot juga memiliki `CREATE TABLE IF NOT EXISTS` sebagai perlindungan tambahan, tetapi migration SQL tetap perlu disimpan sebagai acuan deployment.

## Pengaturan dashboard

Buka **Pengaturan Bot → Link Grup Tele & WhatsApp → Tambah Link**, lalu buat maksimal satu konfigurasi aktif untuk masing-masing platform.

### Telegram

- **Link undangan**: URL HTTPS group/channel Telegram.
- **ID grup**: ID numerik seperti `-1001234567890`. ID ini juga harus cocok dengan bot Telegram.

### WhatsApp

- **Link undangan**: URL `https://chat.whatsapp.com/...` yang ingin diberikan kepada client.
- **ID grup**: ID WhatsApp seperti `120363409784208851@g.us`. Gunakan perintah `!idgrup` pada bot jika perlu mengetahui ID.

## Alur client

1. Client menyelesaikan pendaftaran lewat bot Telegram.
2. Bot meminta konfigurasi link dari endpoint publik CI4.
3. Bot mengirim link Telegram dan WhatsApp dalam satu pesan.
4. Jika client sudah VIP tetapi belum berada di grup Telegram, pesan dua link juga dikirim ulang.
5. Saat client baru masuk grup WhatsApp, bot mengirim event ke CI4.
6. CI4 membandingkan nomor secara canonical (`08...` dan `628...` dianggap sama).
7. Jika nomor sudah ada di `tb_member_vip`, tidak dibuat catatan baru.
8. Jika belum ada, data disimpan di `whatsapp_group_members` dengan unique key `(whatsapp_id, group_id)`, sehingga event berulang tidak menghasilkan duplikat.

## Kick dari dashboard

Gunakan aksi hapus member pada **Database Klien VIP**. Dashboard akan memanggil:

- `/kick-telegram` bila `id_telegram` tersedia.
- `/kick-member` bila `no_wa` tersedia.

Setelah request kick dikirim, proses pembersihan data dan log lama tetap mengikuti perilaku dashboard yang sudah ada. Jika bot WhatsApp belum terhubung atau ID grup salah, dashboard menampilkan peringatan kegagalan kick pada flash message.

## Persiapan bot WhatsApp

1. Deploy `bot_copy.js` dari repository bot.
2. Pastikan dependency `whatsapp-web.js` terpasang.
3. Jalankan bot dan scan QR melalui menu koneksi WhatsApp.
4. Pastikan akun bot adalah admin grup WhatsApp agar `removeParticipants` dapat berjalan.
5. Pastikan `CI_BASE_URL` menunjuk ke aplikasi CI4, contoh `http://202.10.34.128/bot_wa`.
6. Setelah login, uji status endpoint `/api/bot-status` dan uji kick hanya pada akun uji.

Perubahan link tidak memerlukan restart selama bot berhasil membaca endpoint CI4; bot menyegarkan konfigurasi secara berkala.
