# Frontend vendor assets

Folder ini menyimpan salinan statis library UI yang sebelumnya dimuat dari CDN. Halaman admin, login, VIP Console, tabel, grafik, ikon, dan QR code sekarang membaca asset dari `public/assets/vendor`; browser tidak perlu mengunduh dependency UI tersebut dari internet saat runtime.

| Library | Versi tersimpan | Lokasi |
|---|---:|---|
| AdminLTE | 4.10.2 | `admin-lte/` |
| Bootstrap JavaScript | 5.3.9 | `bootstrap/` |
| Popper | 2.11.8 | `popper/` |
| OverlayScrollbars | 2.11.0 | `overlayscrollbars/` |
| Bootstrap Icons | 1.13.1 | `bootstrap-icons/font/` |
| jQuery | 3.7.1 | `jquery/` |
| DataTables + Bootstrap 5 adapter | 1.13.7 | `datatables/` |
| Terjemahan DataTables Indonesia | 1.13.7 | `datatables/i18n/id.json` |
| Chart.js | 4.6.1 | `chartjs/` |
| qrcode | 1.5.1 | `qrcode/` |

CSS AdminLTE sudah memuat base CSS Bootstrap; stylesheet Bootstrap terpisah tidak diperlukan. CSS Bootstrap Icons memakai file font relatif `font/fonts/bootstrap-icons.woff2` dan fallback `.woff`, keduanya disertakan. Salinan license yang tersedia ada di `licenses/`; header lisensi/upstream pada distribusi yang lain juga dipertahankan.

Asset berasal dari paket/upstream resmi: AdminLTE, Bootstrap, Popper, OverlayScrollbars, Bootstrap Icons, jQuery, Chart.js dan qrcode melalui npm/jsDelivr, serta DataTables melalui CDN resminya. Versi tercantum pada URL asal saat asset disalin. Saat mengganti atau memperbarui library, pertahankan lisensinya dan perbarui URL lokal pada view terkait.

Catatan: perubahan ini melokalkan **asset statis UI**. Endpoint Telegram, bot, HFM API, database, dan layanan eksternal lain yang memang dipakai aplikasi tetap memerlukan jaringan sesuai fungsinya; vendor assets tidak mengubah sumber data atau backend.
