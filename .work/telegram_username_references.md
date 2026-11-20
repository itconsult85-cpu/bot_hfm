# Catatan referensi username/link Telegram

- Telegram Bot API mendefinisikan `username` sebagai field opsional pada objek User dan Chat; private Chat memiliki `username` bila tersedia. Sumber resmi: https://core.telegram.org/bots/api
- Telegram deep-link `tg://user?id=<id>` adalah abstraksi untuk entity mention/tombol profil Bot API dan dapat diabaikan oleh klien biasa; link publik pengguna memakai username melalui `t.me/<username>`. Sumber resmi: https://core.telegram.org/api/links
- Implikasi implementasi: ID numerik saja tidak selalu dapat diubah menjadi link percakapan. Lookup berbasis bot API/MTProto bersifat best-effort dan bergantung pada akses chat/entity serta keberadaan username publik.
