# Modifikasi ACID Code Wallet

Menu **Modifikasi ACID Code Wallet** tersedia pada sidebar: **Laporan Broker (HFM) → Modifikasi ACID Wallet**.

## Fungsi

Menu ini mengirim request berikut ke API HFM:

```http
POST /api/client-acid-code-modification/wallet/{client_wallet_id}?acid={acid}
```

Request menggunakan Bearer token API HFM yang sama dengan fitur laporan HFM yang sudah ada. Hasil HTTP 200 ditampilkan sebagai notifikasi sukses. Error validasi 422 dan error HTTP lainnya ditampilkan sebagai notifikasi yang mudah dibaca.

## Cara penggunaan

1. Login sebagai admin.
2. Buka **Laporan Broker (HFM) → Modifikasi ACID Wallet**.
3. Pilih member jika ingin mengisi ID secara otomatis. ID pada daftar member digunakan sebagai referensi `client_wallet_id`.
4. Pastikan `client_wallet_id` adalah angka positif dan merupakan ID wallet yang benar di HFM.
5. Isi ACID code baru.
6. Klik **Kirim ke API HFM**, lalu konfirmasi.
7. Periksa notifikasi hasil dari API.

Fitur ini tidak membuat tabel atau mengubah data lokal. Perubahan hanya dikirim ke API HFM.
