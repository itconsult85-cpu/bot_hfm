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

## Konfigurasi server

Salin `.env.example` menjadi `.env` untuk proses Node, kemudian isi `DB_*`, `CI_BASE_URL`, dan `HFM_API_KEY`. Jangan menyimpan nilai asli di Git atau dump SQL. `TELEGRAM_TOKEN`, `GEMINI_API_KEY`, dan `BOT_CONTROL_TOKEN` tetap dibaca dari `bot_globals`; semua secret lama yang pernah masuk source/dump harus dicabut dan dibuat ulang pada provider masing-masing.

Jalankan sekali:

```bash
mysql -u <user> -p <database> < sql/security_hardening.sql
npm ci --omit=dev
npm start
```

API kontrol Node hanya listen pada `127.0.0.1` dan memerlukan header `X-Bot-Control-Token`. CI4 mengambil token dari `bot_globals`, jadi tombol restart/report dan pengiriman tombol WhatsApp tidak lagi mengirim token yang tertanam di source.

## Catatan keamanan

- Login memakai pesan error generik, rotasi session ID, pembatasan 5 percobaan per 15 menit per session, CSRF global untuk halaman admin, cookie `HttpOnly`/`SameSite=Strict`, dan secure cookie pada production.
- Endpoint kontrol menerima token secara constant-time dan tidak membocorkan detail error ke client.
- URL API CI4 dan kredensial HFM tidak lagi hardcode.
- Aplikasi perlu dijalankan di balik HTTPS pada production; `.env` harus memiliki permission terbatas.
- Karena token Telegram/Gemini dan kredensial HFM sudah terekspos pada riwayat lama, rotasi di provider/server tetap wajib dilakukan walaupun nilainya sudah dihapus dari commit terbaru.
