# Catatan implementasi UI AdminLTE 4 untuk hfm_admin

Layout CI4 bersama, navigasi, stylesheet global, halaman login dan VIP Console memakai struktur serta komponen AdminLTE 4. Data bisnis, route, controller, model, CRUD, query dan database tidak diubah. jQuery/DataTables lama dipertahankan karena masih digunakan beberapa halaman.

Versi CDN yang dipasang adalah **AdminLTE 4.10.2**, versi paket terbaru pada registry npm yang diperiksa dan CSS/JS-nya berhasil diambil dari jsDelivr. **4.10.1 memang ada sebagai rilis GitHub tetapi tidak ditemukan sebagai versi paket npm/jsDelivr**, yang menyebabkan error yang dilaporkan. Aset Bootstrap diselaraskan ke 5.3.9; Bootstrap Icons 1.13.1 dan OverlayScrollbars 2.11.0 tetap sesuai contoh resmi AdminLTE.

Rujukan resmi:
- https://registry.npmjs.org/admin-lte/latest — metadata paket `admin-lte@4.10.2`.
- https://cdn.jsdelivr.net/npm/admin-lte@4.10.2/dist/css/adminlte.min.css — CSS AdminLTE 4.10.2 tersedia.
- https://cdn.jsdelivr.net/npm/admin-lte@4.10.2/dist/js/adminlte.min.js — JavaScript AdminLTE 4.10.2 tersedia.
- https://cdn.jsdelivr.net/npm/bootstrap@5.3.9/dist/js/bootstrap.min.js — Bootstrap 5.3.9 tersedia.
- https://adminlte.io/themes/v4/docs/introduction.html — panduan CDN dan prasyarat AdminLTE 4.
- https://adminlte.io/themes/v4/docs/layout-blueprint.html — struktur `.app-wrapper`, `.app-header`, `.app-sidebar`, `.app-main` dan sidebar responsif.
- https://adminlte.io/themes/v4/docs/components/main-sidebar.html — markup sidebar.
- https://adminlte.io/themes/v4/docs/javascript/treeview.html — `data-lte-toggle="treeview"` dan class `menu-open`.
