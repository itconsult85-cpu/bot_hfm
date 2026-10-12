# Perbandingan `bot.js` lampiran vs workspace

> Laporan ini dibuat tanpa mengubah kode aplikasi maupun database.

## 1. Identitas file

| Item | Lampiran | Workspace |
|---|---|---|
| Path | `D:\Aplikasi Web\bot.js` | `D:\xampp\htdocs\hfm_admin\bot.js` |
| Ukuran | 46.000 byte | 58.577 byte |
| Jumlah baris | 1.161 | 1.359 |
| SHA-256 | `7B77F5A2C41E3E61D37827C339F7F4A4485C25D56605908E9409EE06B96E2830` | `7D40C29F6D8FC05C0B370B067960411622C65BBF769563D3B59CD001FD246D6D` |
| Validasi sintaks | Berhasil (`node --check` = 0) | Berhasil (`node --check` = 0) |

**Kesimpulan:** file workspace bukan salinan identik dari lampiran dan lebih panjang **198 baris**. Diff lengkap tersedia di [file diff](./comparison_botjs_attachment_vs_workspace.diff).

## 2. Perubahan konfigurasi dan keamanan

### Lampiran

- Database memakai kredensial hard-coded: `localhost`, user `bonichi`, password berada langsung di source, dan database `db_hfm_bot`.
- API Express memakai `cors()` dengan kebijakan luas.
- Port API selalu `3000`.
- Endpoint memvalidasi token dari body dengan perbandingan string biasa.
- Memakai `child_process.execFile`, tetapi pada alur yang dibandingkan tidak terlihat menjadi fitur utama.

### Workspace

- Menambahkan `require("dotenv").config()`.
- Konfigurasi database dipindahkan ke environment: `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`.
- Port memakai `API_PORT` dari environment dengan default `3000`.
- Menghapus `cors()` dan membatasi API pada `127.0.0.1`.
- Batas body JSON ditambahkan: `64kb`.
- Menambahkan validasi token melalui header `x-bot-control-token` atau body `token`, menggunakan `crypto.timingSafeEqual`.
- Menambahkan `CI_BASE_URL` dan helper URL/header untuk komunikasi ke aplikasi CodeIgniter 4.

**Dampak:** workspace lebih aman dan sesuai deployment server, tetapi `.env` wajib memiliki konfigurasi database, `CI_BASE_URL`, dan token yang benar. Jika environment variable kosong, koneksi database atau komunikasi CI4 dapat gagal.

## 3. Perubahan endpoint Express

| Endpoint | Lampiran | Workspace | Perubahan |
|---|---:|---:|---|
| `/api/restart-bot` | Ada | Ada | Workspace memakai token aman dan `process.exit(0)` untuk process manager. |
| `/api/send-daily-report` | Ada | Ada | Lampiran memanggil fungsi laporan lokal; workspace mengambil data dari endpoint CI4 lalu mengirim ke admin dinamis. |
| `/kick-telegram` | Ada | Tidak ada | Digantikan oleh proses kick massal berbasis kandidat `lepas_ib`. |
| `/reset-user` | Ada | Tidak ada | Endpoint reset memori user dihapus dari workspace. |
| `/api/kick-lepas-ib-members` | Tidak ada | Ada | Kick massal dengan query database, pengecekan status member, retry Telegram, dan pencatatan hasil. |
| `/send-whatsapp-button` | Tidak ada | Ada | Mengambil link WhatsApp aktif dan mengirim tombol deep-link Telegram ke grup. |

## 4. Fitur baru utama di workspace

1. **Link grup dinamis dari CI4** melalui `GET /client/apiGroupLinks`; ID admin dan konfigurasi grup tidak lagi bergantung pada `bot_globals.ID_ADMIN`/`ID_GRUP_VIP` saja.
2. **Deep-link WhatsApp** (`wa_<source_link_id>`): bot memvalidasi status VIP, lalu memberikan link Telegram dan WhatsApp atau mengarahkan user ke proses pendaftaran.
3. **Kick massal member lepas IB** dengan sumber kandidat dari `tb_member_vip` dan `tb_member_logs`, pengecualian member yang sudah keluar, pengecekan ulang sebelum kick, retry rate-limit, dan hasil `kicked/skipped/failed`.
4. **Pelacakan anggota Telegram** melalui event `new_chat_members` dan `left_chat_member` ke `telegram_group_members`.
5. **Atribusi sumber registrasi**: form dapat membawa `registration_source` dan `registration_source_id`.
6. **Penyelesaian progress**: query `user_progress` workspace menulis `completed_at` ketika step mencapai 7.
7. **OCR lebih terarah**: urutan deteksi menjadi bukti pindah IB berhasil, arsip berhasil, multi-ID, satu ID, lalu keyword screenshot akun.
8. **Pencocokan keyword lebih aman** karena keyword di-escape sebelum dibuat menjadi regular expression.
9. **Konfigurasi jadwal lokal dihapus**: fungsi `runDailyReport`, `runActivityReminder`, `refreshConfiguredSchedules`, dan pemanggilan cron dinamis tidak ada lagi pada workspace. Endpoint laporan workspace sekarang mengambil data langsung dari CI4.

## 5. Perubahan alur bisnis bot

- Lampiran menggunakan link invite Telegram sementara yang dibuat dengan `createChatInviteLink`.
- Workspace memakai link Telegram/WhatsApp yang dikelola melalui dashboard/CI4; bila link belum aktif, user diberi pesan konfigurasi belum lengkap.
- Lampiran menerima form setelah ID tervalidasi di memori; workspace mengikat data form dengan `currentStep === 7` dan dapat menyimpan metadata sumber registrasi.
- Lampiran meminta ID dengan pola `5–15` digit; workspace memakai minimal `5` digit tanpa batas atas.
- Workspace mencegah user biasa yang masih berada sebelum step 5 mengirim ID lebih awal.
- Pencatatan progress workspace dipindah ke blok `finally` untuk user non-admin sehingga beberapa jalur balasan tidak lagi menulis progress di tengah alur.

## 6. Dampak terhadap database dan SQL

Workspace menggunakan/menuntut objek berikut:

- `bot_globals`, `bot_flows`, `bot_faqs`, `chat_logs_tele`, `user_progress`, dan `tb_panduan_media` (sudah dipakai oleh alur bot lama maupun baru).
- `tb_member_vip.id_telegram` dan `tb_member_vip.no_wa` untuk pencocokan member.
- `tb_member_logs.id_telegram` dan `tb_member_logs.tipe_aktivitas` untuk kandidat `lepas_ib`.
- `bot_group_links` untuk link grup Telegram/WhatsApp.
- `telegram_group_members` untuk status masuk/keluar member Telegram.
- Kolom `tb_member_vip.registration_source` dan `registration_source_id`.
- Kolom `user_progress.completed_at` dipakai oleh query workspace; kolom ini perlu diverifikasi karena tidak ditemukan dalam pencarian file SQL yang tersedia.

File `sql/server_schema_sync.sql` sudah mencakup `bot_group_links`, `telegram_group_members`, `tb_member_logs.id_telegram`, dan metadata registrasi. File `sql/bot_schedules.sql` hanya mengatur jadwal/token dan tidak diperlukan untuk perbandingan kode ini.

**Tidak ada perubahan database yang dilakukan dalam tugas ini, sehingga tidak ada SQL migrasi baru yang perlu diberikan.** Namun sebelum deploy workspace, verifikasi kolom `user_progress.completed_at` pada database target. Jika kolom itu belum ada, bot akan gagal mencatat progress dengan error SQL.

## 7. Temuan yang perlu diperhatikan

- Pada handler `bot.start` versi workspace terdapat dua `return ctx.reply(...)` identik berturut-turut pada cabang payload yang tidak cocok. `return` kedua tidak pernah dijalankan. Ini tidak menyebabkan syntax error, tetapi sebaiknya dihapus agar kode bersih.
- Endpoint `/reset-user` hilang dari workspace. Jika dashboard CodeIgniter masih memanggil endpoint tersebut, fitur reset memori akan menerima 404.
- Endpoint `/kick-telegram` juga hilang. Dashboard harus menggunakan `/api/kick-lepas-ib-members` dengan header/token baru dan payload yang sesuai.
- Karena CORS dihapus dan API bind ke `127.0.0.1`, endpoint harus dipanggil dari server lokal yang sama; pemanggilan langsung dari browser atau host berbeda tidak lagi sama perilakunya.
- Laporan harian workspace tidak lagi memakai `runDailyReport`/scheduler lokal. Penjadwalan harus ditangani oleh CI4/dashboard/process manager atau mekanisme lain yang memang menjalankan endpoint tersebut.
- `cron` masih di-import pada workspace, tetapi pemakaian scheduler dinamis yang ada di lampiran sudah dihapus; dependensi/import ini layak dibersihkan pada revisi terpisah jika memang tidak dipakai di bagian lain.

## 8. Artefak perbandingan

- [Laporan perbandingan ini](./comparison_botjs_attachment_vs_workspace.md)
- [Diff lengkap dengan konteks baris](./comparison_botjs_attachment_vs_workspace.diff)
