# Tombol Grup WhatsApp melalui Bot Telegram

## Arsitektur

Fitur ini menggunakan **satu bot Telegram**. Tidak diperlukan client WhatsApp atau session WhatsApp untuk alur pendaftaran.

Link WhatsApp asli tidak dibagikan langsung oleh dashboard. Dashboard meminta bot mengirim tombol perantara ke grup Telegram.

```text
Dashboard
  -> bot Telegram mengirim tombol ke grup Telegram
  -> user klik tombol
  -> Telegram membuka deep-link bot: /start wa_<source_link_id>
  -> bot mengecek database
  -> user lama langsung mendapat link WhatsApp
  -> user baru mengisi form dan memvalidasi ID Trading
```

## Pengaturan dashboard

Buka **Pengaturan Bot → Link Grup Tele & WhatsApp**.

Di bagian atas tabel tersedia input **ID Admin Telegram**. Nilai awal diambil dari key `ID_ADMIN` yang sudah ada di `bot_globals`; menyimpan perubahan dari halaman ini akan memperbarui record yang sama. Key `ID_ADMIN` tidak lagi ditampilkan atau dapat diubah dari menu **Bot Global**.

Konfigurasi grup Telegram sebelumnya yang tersimpan sebagai `ID_GRUP_VIP` di `bot_globals` akan dimigrasikan ke baris platform Telegram pada `bot_group_links`. Setelah migration, `ID_GRUP_VIP` tidak lagi digunakan oleh bot dan tidak ditampilkan sebagai input di menu **Bot Global**.

Buat dua konfigurasi aktif:

1. **Telegram**
   - Link Telegram.
   - ID grup Telegram tempat tombol akan dikirim.
2. **WhatsApp**
   - Link undangan WhatsApp asli.
   - Nama grup WhatsApp.
   - ID source otomatis berasal dari kolom `id` tabel `bot_group_links`.

`bot.js` workspace membaca `group_id` Telegram dan `invite_link` Telegram/WhatsApp melalui endpoint `client/apiGroupLinks`, sehingga perubahan dari dashboard dapat dipakai tanpa input ulang di `bot_globals`.
Endpoint yang sama juga mengirim `admin_id` terbaru. Bot memakai nilai tersebut untuk mengenali admin, membuat tautan chat admin, dan mengganti ID admin tanpa perlu mengubah file bot.

Pada baris link WhatsApp, klik tombol **kirim**. Bot akan mengirim tombol **Gabung Grup WhatsApp** ke grup Telegram yang dikonfigurasi.

## Pengecekan user lama

Saat user menekan tombol:

1. Telegram membuka bot dengan payload `wa_<id_link>`, contoh `wa_12`.
2. Bot meminta endpoint `check_status_vip` untuk mengecek `id_telegram`.
3. Jika user sudah terdaftar dan aktif, bot langsung menampilkan tombol menuju link WhatsApp asli.
4. User tidak diminta mengisi form ulang.

## Pendaftaran user baru

Jika `id_telegram` belum ada di database:

1. Bot meminta ID Trading.
2. Bot memanggil endpoint `cekHfm`.
3. Jika ID valid dan memenuhi syarat, bot meminta:
   - Nama
   - Nomor WhatsApp
   - Email
4. Bot memanggil `simpanMemberForm`.
5. Data disimpan ke `tb_member_vip`.
6. Kolom source diisi:
   - `registration_source = telegram_whatsapp_button`
   - `registration_source_id = id konfigurasi link WhatsApp`
7. Bot mengirim tombol menuju link WhatsApp.

Jika ID Trading invalid, sudah diklaim, atau deposit kurang, bot menampilkan pesan validasi dan tidak menyimpan member baru.

## Database

Jalankan migration berikut satu kali setelah backup:

```text
sql/telegram_whatsapp_button_source.sql
```

Migration menambahkan metadata source ke `tb_member_vip` dan tidak menghapus atau mengubah data lama.

Contoh hasil pencatatan:

| id_telegram | id_hfm | registration_source | registration_source_id |
|---|---|---|---:|
| 123456789 | 198484049 | telegram_whatsapp_button | 12 |

Dengan demikian admin dapat mengetahui bahwa user berasal dari tombol WhatsApp konfigurasi nomor 12.

## Endpoint bot baru

```text
POST /send-whatsapp-button
```

Dipanggil dashboard dengan payload:

```json
{
  "source_link_id": 12,
  "telegram_group_id": "-1001234567890"
}
```

Endpoint membuat deep-link bot otomatis berdasarkan username bot Telegram dan mengirim tombol ke grup.

## Catatan penting

Bot Telegram tidak dapat menerima event ketika user benar-benar menekan link WhatsApp atau selesai bergabung ke WhatsApp. Karena itu, pemeriksaan dilakukan **sebelum link WhatsApp diberikan**, melalui deep-link Telegram.
