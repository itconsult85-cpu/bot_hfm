# Telegram registration bot

## Status audit

`bot.js` sekarang disimpan di repository dan menggunakan tabel yang tersedia pada dump SQL:

- `bot_globals`: token Telegram, Gemini, URL pendaftaran, kode IB, admin, timeout, dan token kontrol.
- `bot_flows`: urutan tahap pendaftaran dan kata pemicu.
- `bot_faqs`: jawaban FAQ dan marker media.
- `tb_panduan_media`: URL video/gambar yang dikirim oleh marker seperti `[VIDEO: ktp]`.
- `user_progress`: tahap terakhir pendaftar.
- `chat_logs_tele`: log pesan user/bot.
- `tb_member_vip` dan `tb_member_logs`: hasil validasi/pendaftaran member.
- `bot_group_links`: link Telegram/WhatsApp aktif.

Struktur tabel tidak diubah oleh hardening ini. SQL tambahan hanya memastikan `BOT_CONTROL_TOKEN` tersedia tanpa menimpa token yang sudah ada.

## Alur pendaftaran

1. `/start` mengatur user ke tahap 1 dan mengirim sapaan dari `KATA_SAPAAN`.
2. Bot mencocokkan kata pemicu dengan FAQ/flow dari database.
3. Bot mengarahkan user melalui pendaftaran akun, screenshot akun, pindah IB, arsip akun, pembukaan MT5/deposit, dan pengisian formulir.
4. ID trading yang dikirim sebagai teks atau terbaca dari screenshot divalidasi melalui endpoint CI4 `cekHfm`.
5. Jika valid dan deposit memenuhi batas mata uang, bot meminta nama, nomor WhatsApp, dan email.
6. Form disimpan melalui `simpanMemberForm`; CI4 mengambil data HFM dan mencatat `tb_member_vip` serta `tb_member_logs`.
7. Bot membuat/mengambil link undangan Telegram dan menampilkan link grup WhatsApp aktif.
8. Pesan, progres, timeout follow-up, dan media dicatat/dikelola sesuai tabel terkait.

## Permintaan bantuan admin

Saat user terkendala dan menekan tombol **Tolong saya admin** atau mengetik **tolong saya admin**, bot mengirim ringkasan satu kali kepada admin: identitas user, tahap kendala, keterangan terakhir user, dan hingga 10 foto terbaru yang tercatat. Bot tidak mengirim salinan seluruh riwayat chat dan tidak meneruskan pesan lanjutan. Tombol **Buka chat user** mengarahkan admin ke profil Telegram agar admin menghubungi user langsung; tidak ada perintah balas atau tutup sesi melalui bot.

Daftar **Permintaan Bantuan Admin** pada dashboard CI4 dibaca dari `chat_logs_tele`, sehingga tidak bergantung pada API runtime Node dan tetap tersedia setelah bot di-restart. Permintaan baru masuk ke daftar setelah bot berhasil mengirim konfirmasi laporan dan mencatatnya ke database.

Setelah status user tervalidasi dan undangan grup dikirim, keyboard bantuan dihapus. Pesan berikutnya hanya mendapat pemberitahuan bahwa akun sudah tervalidasi/terdaftar; status VIP diperiksa sebelum tombol bantuan ditawarkan sehingga user tervalidasi tidak masuk alur bantuan pendaftaran lagi.

## Konfigurasi server

Untuk bot VPS, pertahankan konfigurasi yang sudah dipakai oleh baseline stabil. Jangan menambahkan loader yang memaksa bot membaca atau menimpa nilainya dari `.env` CI4. Konfigurasi CI4 dan proses bot dikelola terpisah; jangan menyalin isi rahasia `.env` ke Git, dump SQL, atau percakapan.

Jalankan sekali. Jika memakai phpMyAdmin, pilih database CI4 pada panel kiri terlebih dahulu, lalu buka tab **SQL** dan jalankan seluruh isi file. Error MySQL `#1046 No database selected` berarti database belum dipilih.

```bash
mysql -u <user> -p <database> < sql/security_hardening.sql
npm ci --omit=dev
npm start
```

API kontrol Node di port `3000` memerlukan header `X-Bot-Control-Token`. Request pemeriksaan status VIP dari bot juga memakai token tersebut; HTTP `401` berarti token tidak terkirim atau nilainya tidak cocok. Samakan konfigurasi `BOT_CONTROL_TOKEN` yang dipakai proses bot dengan nilai di database CI4, tanpa membagikan tokennya.

Di halaman **Progress Pendaftar Bot**, tombol **Reset** pada setiap baris mengembalikan progres ke Tahap 1 dan menghapus state sementara bot untuk user tersebut. Fitur ini tidak menghapus riwayat chat atau mengubah status VIP maupun keanggotaan grup.

## Catatan keamanan

- Login memakai pesan error generik, rotasi session ID, pembatasan 5 percobaan per 15 menit per session, CSRF global untuk halaman admin, cookie `HttpOnly`/`SameSite=Strict`, dan secure cookie pada production.
- Endpoint kontrol menerima token secara constant-time dan tidak membocorkan detail error ke client.
- URL API CI4 dan kredensial HFM tidak lagi hardcode.
- Aplikasi perlu dijalankan di balik HTTPS pada production; `.env` harus memiliki permission terbatas.
- Karena token Telegram/Gemini dan kredensial HFM sudah terekspos pada riwayat lama, rotasi di provider/server tetap wajib dilakukan walaupun nilainya sudah dihapus dari commit terbaru.
