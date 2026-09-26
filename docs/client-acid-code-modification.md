# Modifikasi ACID Code Wallet dan Trading Account

## Modifikasi ACID Wallet

Menu **Laporan Broker (HFM) → Modifikasi ACID Wallet** memanggil:

```http
POST /api/client-acid-code-modification/wallet/{client_wallet_id}?acid={acid}
```

## Modifikasi ACID Trading Account

Menu **Laporan Broker (HFM) → Modifikasi ACID Trading Account** memanggil:

```http
POST /api/client-acid-code-modification/trading-account/{client_trading_account_id}?acid={acid}
```

Kedua request menggunakan Bearer token API HFM yang sama dengan fitur laporan HFM yang sudah ada. Hasil HTTP 200 ditampilkan sebagai notifikasi sukses. Error validasi 422 dan error HTTP lainnya ditampilkan sebagai notifikasi yang mudah dibaca.

## Cara penggunaan

1. Login sebagai admin.
2. Buka grup **Laporan Broker (HFM)** pada sidebar.
3. Pilih menu sesuai target: **Modifikasi ACID Wallet** atau **Modifikasi ACID Trading Account**.
4. Pilih member sebagai referensi jika diperlukan, atau isi ID secara manual.
5. Pastikan ID sesuai target API. Untuk menu trading account, gunakan `client_trading_account_id`, bukan wallet ID.
6. Isi ACID code baru.
7. Klik **Kirim ke API HFM**, lalu konfirmasi.
8. Periksa notifikasi hasil dari API.

Kedua fitur ini tidak membuat tabel atau mengubah data lokal. Perubahan hanya dikirim ke API HFM.
