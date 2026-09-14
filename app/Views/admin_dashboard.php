<?= $this->extend('layout/template') ?>

<?= $this->section('styles') ?>
<style>
    .metric-card,
    .data-card {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .metric-card {
        border-radius: 20px;
    }

    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 25px rgba(0, 0, 0, 0.04);
    }

    .data-card {
        border-radius: 16px;
    }

    .data-card:hover {
        transform: translateX(4px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.03);
        border-color: #cbd5e1;
    }

    .metric-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .avatar-sub {
        width: 46px;
        height: 46px;
        background: #f1f5f9;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #475569;
        flex-shrink: 0;
    }

    .row-num {
        min-width: 32px;
        display: inline-block;
        font-weight: 600;
    }

    .info-detail span {
        white-space: nowrap;
    }

    .modal-content {
        border-radius: 20px;
        border: none;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
        padding: 0.6rem 1rem;
    }

    /* Tombol aksi: sejajar kanan di desktop, di bawah di mobile */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: flex-start;
    }

    @media (min-width: 992px) {
        .action-buttons {
            justify-content: flex-end;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 mt-2 gap-3">
    <div>
        <h3 class="fw-bold tracking-tight mb-1">Overview Realtime</h3>
        <p class="text-muted small mb-0">Pantau performa pendaftaran dan aktivitas grup VIP Anda.</p>
    </div>
    <div>
        <a href="<?= base_url('AdminDashboard/backupDatabase') ?>" class="btn btn-outline-dark rounded-pill px-4 fw-semibold shadow-sm">
            <i class="bi bi-database-down me-2"></i>Backup Database
        </a>
    </div>
</div>

<?php if (session()->getFlashdata('pesan')): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 py-2 mb-4 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('pesan') ?>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-5">
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center">
                <div class="metric-icon bg-primary-subtle text-primary me-3"><i class="bi bi-people-fill"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Total Member Aktif</p>
                    <h3 class="fw-bold mb-0 text-dark"><?= $aktif_total ?> <span class="fs-6 text-muted fw-normal">User</span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center">
                <div class="metric-icon bg-success-subtle text-success me-3"><i class="bi bi-person-plus-fill"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Daftar Baru (Hari Ini)</p>
                    <h3 class="fw-bold mb-0 text-dark"><?= $baru_hari_ini ?> <span class="fs-6 text-muted fw-normal">User</span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center">
                <div class="metric-icon bg-danger-subtle text-danger me-3"><i class="bi bi-box-arrow-right"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Lepas IB (Hari Ini)</p>
                    <h3 class="fw-bold mb-0 text-dark"><?= $lepas_hari_ini ?> <span class="fs-6 text-muted fw-normal">User</span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center">
                <div class="metric-icon bg-warning-subtle text-warning-emphasis me-3"><i class="bi bi-clock-history"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Pasif > 30 Hari</p>
                    <h3 class="fw-bold mb-0 text-dark"><?= $pasif_total ?> <span class="fs-6 text-muted fw-normal">User</span></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold tracking-tight mb-1">Database Klien VIP</h4>
        <p class="text-muted small mb-0">Kelola dan sinkronkan data member secara realtime.</p>
    </div>
    <div class="d-flex flex-column flex-md-row gap-2">
        <select id="filterStatus" class="form-select rounded-pill px-3" style="min-width: 140px;">
            <option value="">Semua Status</option>
            <option value="aktif">Aktif</option>
            <option value="lepas_ib">Lepas IB</option>
        </select>
        <select id="filterCurrency" class="form-select rounded-pill px-3" style="min-width: 140px;">
            <option value="">Semua Akun</option>
            <option value="USD">USD</option>
            <option value="USC">USC</option>
            <option value="IDR">IDR</option>
        </select>
        <input type="text" id="searchInput" class="form-control rounded-pill px-4" placeholder="Cari Nama, ID, WA, atau Tele..." style="min-width: 250px;">
        <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm text-nowrap" onclick="syncSemua()" id="btnSyncSemua">
            <i class="bi bi-cloud-arrow-down-fill me-2"></i> Sync Semua
        </button>
    </div>
</div>

<div class="row g-3" id="memberContainer">
    <?php foreach ($members as $index => $m): ?>
        <div class="col-12 member-item"
            data-nama="<?= strtolower(esc($m['nama'] ?? '')) ?>"
            data-idhfm="<?= esc($m['id_hfm']) ?>"
            data-nowa="<?= esc($m['no_wa']) ?>"
            data-idtelegram="<?= strtolower(esc($m['id_telegram'] ?? '')) ?>"
            data-status="<?= esc($m['status']) ?>"
            data-currency="<?= esc($m['currency'] ?? 'USD') ?>">
            <div class="card data-card p-3">
                <div class="row align-items-center g-3">

                    <!-- Kolom 1: Nomor urut -->
                    <div class="col-auto">
                        <span class="row-num text-muted">#<?= $index + 1 ?></span>
                    </div>

                    <!-- Kolom 2: Avatar + Nama + Detail (WA, Telegram) -->
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-sub">
                                <?php
                                $nama_member = trim($m['nama'] ?? '');
                                if (empty($nama_member) || $nama_member == 'Member BOSSCUAN') {
                                    $inisial = 'MB';
                                } else {
                                    $pecah = explode(' ', $nama_member);
                                    if (count($pecah) > 1) {
                                        $inisial = strtoupper(substr($pecah[0], 0, 1) . substr($pecah[1], 0, 1));
                                    } else {
                                        $inisial = strtoupper(substr($nama_member, 0, 2));
                                    }
                                }
                                ?>
                                <?= $inisial ?>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <h6 class="fw-bold mb-1 text-dark text-truncate" id="nama-<?= $m['id_hfm'] ?>">
                                    <?= isset($m['nama']) && !empty($m['nama']) ? esc($m['nama']) : 'Member BOSSCUAN' ?>
                                </h6>
                                <div class="text-muted small info-detail d-flex flex-wrap gap-2">
                                    <span><i class="bi bi-hash text-primary"></i> <?= esc($m['id_hfm']) ?></span>
                                    <span><i class="bi bi-whatsapp text-success"></i> <?= esc($m['no_wa']) ?></span>
                                    <span><i class="bi bi-telegram text-info"></i> <?= esc($m['id_telegram'] ?? '-') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 3: Deposit -->
                    <div class="col-lg-2 col-md-6 col-6">
                        <p class="text-muted small mb-0">Deposit</p>
                        <span class="fw-bold text-dark" id="depo-<?= $m['id_hfm'] ?>">
                            <?= esc($m['currency'] ?? 'USD') ?> <?= number_format($m['deposit'], 2) ?>
                        </span>
                    </div>

                    <!-- Kolom 4: Terakhir Trading -->
                    <div class="col-lg-2 col-md-6 col-6">
                        <p class="text-muted small mb-0">Terakhir Trading</p>
                        <span id="trade-<?= $m['id_hfm'] ?>">
                            <?php if (empty($m['last_trade']) || $m['last_trade'] === '0000-00-00 00:00:00'): ?>
                                <span class="badge bg-light border text-muted rounded-pill small">Belum Trading</span>
                            <?php else: ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill fw-semibold small">
                                    <i class="bi bi-graph-up me-1"></i><?= date('d M Y', strtotime($m['last_trade'])) ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Kolom 5: Status IB -->
                    <div class="col-lg-1 col-md-6 col-6">
                        <p class="text-muted small mb-0">Status IB</p>
                        <span id="status-<?= $m['id_hfm'] ?>">
                            <?php if ($m['status'] == 'aktif'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small fw-semibold">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small fw-semibold">Lepas IB</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- Kolom 6: Tombol Aksi -->
                    <div class="col-lg-3 col-md-12 col-12">
                        <div class="action-buttons">
                            <button class="btn btn-outline-warning btn-sm rounded-pill px-3 fw-semibold btn-edit"
                                data-id="<?= esc($m['id_hfm']) ?>"
                                data-wa="<?= esc($m['no_wa']) ?>"
                                data-currency="<?= esc($m['currency'] ?? 'USD') ?>"
                                data-deposit="<?= esc($m['deposit']) ?>"
                                data-status="<?= esc($m['status']) ?>"
                                data-trade="<?= esc($m['last_trade']) ?>">
                                <i class="bi bi-pencil-square me-1"></i> Edit
                            </button>

                            <button class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold btn-sync-single"
                                onclick="syncData(this, '<?= $m['id_hfm'] ?>')">
                                <i class="bi bi-arrow-repeat me-1"></i> Cek API
                            </button>

                            <button class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold btn-hapus"
                                data-id="<?= esc($m['id_hfm']) ?>"
                                data-nama="<?= esc($m['nama'] ?? 'Member BOSSCUAN') ?>"
                                data-bs-toggle="modal" data-bs-target="#modalHapus">
                                <i class="bi bi-trash3 me-1"></i> Hapus
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4">
    <span class="text-muted small mb-2 mb-md-0" id="pageInfo">Menampilkan 0 data</span>
    <nav>
        <ul class="pagination pagination-sm mb-0 flex-wrap" id="paginationControls"></ul>
    </nav>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold mb-0">Edit Data Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditMember">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">ID HFM (Bisa diedit)</label>
                        <input type="hidden" id="edit_old_id" name="old_id_hfm">
                        <input type="text" class="form-control" id="edit_id" name="id_hfm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Nomor WA</label>
                        <input type="text" class="form-control" id="edit_wa" name="no_wa" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-semibold text-muted">Mata Uang</label>
                            <select class="form-select" id="edit_currency" name="currency">
                                <option value="USD">USD</option>
                                <option value="USC">USC</option>
                                <option value="IDR">IDR</option>
                            </select>
                        </div>
                        <div class="col-8">
                            <label class="form-label small fw-semibold text-muted">Nominal Deposit</label>
                            <input type="number" step="0.01" class="form-control" id="edit_deposit" name="deposit" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-5">
                            <label class="form-label small fw-semibold text-muted">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="aktif">Aktif</option>
                                <option value="lepas_ib">Lepas IB</option>
                            </select>
                        </div>
                        <div class="col-7">
                            <label class="form-label small fw-semibold text-muted">Last Trade</label>
                            <input type="text" class="form-control" id="edit_trade" name="last_trade" placeholder="YYYY-MM-DD HH:MM:SS">
                            <small class="text-muted" style="font-size: 0.7rem;">Isi 0000-00-00 00:00:00 jika belum trade</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg">
            <div class="modal-body p-4 text-center">
                <div class="display-6 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h5 class="fw-bold mb-2">Hapus Member?</h5>
                <p class="text-muted small">Apakah Anda yakin ingin menghapus data member <br>
                    <strong class="text-dark" id="teksNamaHapus"></strong>
                    dengan ID <strong class="text-dark" id="teksIdHapus"></strong> secara permanen?
                </p>
                <div class="d-grid gap-2 mt-4">
                    <a href="#" id="linkHapusModal" class="btn btn-danger rounded-pill py-2 fw-semibold">
                        <i class="bi bi-trash3 me-2"></i>Ya, Hapus Permanen
                    </a>
                    <button type="button" class="btn btn-link text-muted btn-sm text-decoration-none" data-bs-dismiss="modal">Batalkan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalQR" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg text-center p-4">
            <h5 class="fw-bold mb-3">Koneksi WhatsApp</h5>
            <div id="qrContainer" class="mb-3 d-none">
                <canvas id="qrCanvas" class="img-fluid rounded shadow-sm border p-2"></canvas>
                <p class="text-muted small mt-2">Scan QR code ini untuk menghubungkan bot WhatsApp.</p>
            </div>
            <div id="statusContainer" class="p-3 rounded bg-light mb-0">
                <span id="botStatusBadge" class="badge bg-secondary p-2 px-3 rounded-pill">Mengecek status...</span>
            </div>
            <div class="d-grid gap-2 mt-3">
                <button class="btn btn-warning rounded-pill" onclick="restartBot()"><i class="bi bi-arrow-clockwise me-1"></i> Restart Bot</button>
                <button class="btn btn-danger rounded-pill" onclick="resetSesi()"><i class="bi bi-trash me-1"></i> Reset Sesi (Ganti Nomor)</button>
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<script>
    document.querySelectorAll('.btn-hapus').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.getAttribute('data-id');
            const nama = this.getAttribute('data-nama') || 'Member BOSSCUAN';
            document.getElementById('teksIdHapus').innerText = id;
            document.getElementById('teksNamaHapus').innerText = nama;
            const linkHapus = document.getElementById('linkHapusModal');
            if (linkHapus) linkHapus.href = "<?= base_url('AdminDashboard/hapus-member/') ?>" + id;
        });
    });

    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_old_id').value = this.getAttribute('data-id');
            document.getElementById('edit_wa').value = this.getAttribute('data-wa');
            document.getElementById('edit_currency').value = this.getAttribute('data-currency');
            document.getElementById('edit_deposit').value = this.getAttribute('data-deposit');
            document.getElementById('edit_status').value = this.getAttribute('data-status');
            document.getElementById('edit_trade').value = this.getAttribute('data-trade');
            new bootstrap.Modal(document.getElementById('modalEdit')).show();
        });
    });

    async function syncData(btn, id_hfm, isBulk = false) {
        try {
            btn.innerHTML = '<i class="bi bi-hourglass-split spinner-border spinner-border-sm me-1"></i> Loading...';
            btn.disabled = true;
            btn.classList.replace('btn-outline-primary', 'btn-light');

            const targetUrl = "<?= base_url('AdminDashboard/syncHfm/') ?>" + id_hfm;
            const response = await fetch(targetUrl);

            if (!response.ok) {
                alert("Server Error! HTTP Status: " + response.status);
                throw new Error("HTTP Error " + response.status);
            }

            const resText = await response.text();
            let res;
            try {
                res = JSON.parse(resText);
            } catch (e) {
                alert("Sistem Error! Server tidak mengembalikan data JSON.");
                throw new Error("Invalid JSON");
            }

            if (res.status === 'sukses') {
                let currency = (res.data.currency && res.data.currency !== '') ? res.data.currency : 'USD';
                let depoVal = parseFloat(res.data.deposit);
                let depoFormatted = currency === 'IDR' ? currency + ' ' + depoVal.toLocaleString('id-ID') : currency + ' ' + depoVal.toFixed(2);

                let depoEl = document.getElementById('depo-' + id_hfm);
                if (depoEl) depoEl.innerText = depoFormatted;

                if (res.data.nama) {
                    let elemNama = document.getElementById('nama-' + id_hfm);
                    if (elemNama) elemNama.innerText = res.data.nama;
                }

                let rawTrade = res.data.last_trade;
                let tradeEl = document.getElementById('trade-' + id_hfm);
                if (tradeEl) {
                    if (rawTrade && rawTrade !== '0000-00-00 00:00:00' && rawTrade !== 'N/A') {
                        if (rawTrade.includes('-')) {
                            let parts = rawTrade.split(' ')[0].split('-');
                            let bulanIndo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                            let tgl = parseInt(parts[2]) + ' ' + bulanIndo[parseInt(parts[1]) - 1] + ' ' + parts[0];
                            tradeEl.innerHTML = `<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill fw-semibold small"><i class="bi bi-graph-up me-1"></i>${tgl}</span>`;
                        }
                    } else {
                        tradeEl.innerHTML = `<span class="badge bg-light border text-muted rounded-pill small">Belum Trading</span>`;
                    }
                }

                let statusEl = document.getElementById('status-' + id_hfm);
                if (statusEl) statusEl.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small fw-semibold">Aktif</span>';

                const row = document.querySelector(`.member-item[data-idhfm="${id_hfm}"]`);
                if (row) row.setAttribute('data-status', 'aktif');

            } else if (res.status === 'lepas_ib') {
                let statusEl = document.getElementById('status-' + id_hfm);
                if (statusEl) statusEl.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small fw-semibold">Lepas IB</span>';

                const row = document.querySelector(`.member-item[data-idhfm="${id_hfm}"]`);
                if (row) row.setAttribute('data-status', 'lepas_ib');

            } else {
                alert("Gagal Sync: " + (res.pesan || "Terjadi kesalahan sistem"));
            }

            if (!isBulk && typeof window.applyFilters === 'function') window.applyFilters();

        } catch (err) {
            console.error('Terjadi Error di JS:', err);
        } finally {
            btn.innerHTML = '<i class="bi bi-check-circle-fill text-success me-1"></i> Selesai';
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Cek API';
                btn.disabled = false;
                btn.classList.replace('btn-light', 'btn-outline-primary');
            }, 2000);
        }
    }

    async function syncSemua() {
        const btnSemua = document.getElementById('btnSyncSemua');

        const itemsToSync = typeof window.getFilteredItems === 'function' ?
            window.getFilteredItems() :
            Array.from(document.querySelectorAll('.member-item'));

        if (itemsToSync.length === 0) {
            alert("Tidak ada data member untuk di-sync.");
            return;
        }

        btnSemua.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyinkronkan...';
        btnSemua.disabled = true;

        for (let i = 0; i < itemsToSync.length; i++) {
            let item = itemsToSync[i];
            let btn = item.querySelector('.btn-sync-single');
            let id_hfm = item.getAttribute('data-idhfm');

            if (btn && id_hfm) {
                await syncData(btn, id_hfm, true);

                await new Promise(resolve => setTimeout(resolve, 1000));
            }
        }

        if (typeof window.applyFilters === 'function') window.applyFilters();

        btnSemua.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> Sinkronisasi Selesai';
        btnSemua.classList.replace('btn-primary', 'btn-success');

        setTimeout(() => {
            btnSemua.innerHTML = '<i class="bi bi-cloud-arrow-down-fill me-2"></i> Sync Semua';
            btnSemua.classList.replace('btn-success', 'btn-primary');
            btnSemua.disabled = false;
        }, 3000);
    }

    document.addEventListener("DOMContentLoaded", function() {
        const itemsPerPage = 100;
        let currentPage = 1;
        const allItems = Array.from(document.querySelectorAll('.member-item'));
        let filteredItems = [...allItems];
        window.getFilteredItems = () => filteredItems;
        const paginationControls = document.getElementById('paginationControls');
        const pageInfo = document.getElementById('pageInfo');

        function renderPage(page) {
            currentPage = page;
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            allItems.forEach(item => item.classList.add('d-none'));
            const paginatedItems = filteredItems.slice(start, end);
            paginatedItems.forEach(item => item.classList.remove('d-none'));
            updatePaginationInfo();
            renderPaginationControls();
        }

        function updatePaginationInfo() {
            const total = filteredItems.length;
            if (total === 0) {
                pageInfo.innerHTML = "Tidak ada data ditemukan.";
                return;
            }
            const start = ((currentPage - 1) * itemsPerPage) + 1;
            const end = Math.min(currentPage * itemsPerPage, total);
            pageInfo.innerHTML = `Menampilkan <b>${start}</b> hingga <b>${end}</b> dari <b>${total}</b> data`;
        }

        function renderPaginationControls() {
            paginationControls.innerHTML = '';
            const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
            if (totalPages <= 1) return;

            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link shadow-sm rounded-start-pill" href="#">&laquo;</a>`;
            prevLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage > 1) renderPage(currentPage - 1);
            });
            paginationControls.appendChild(prevLi);

            let maxVisibleButtons = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisibleButtons / 2));
            let endPage = Math.min(totalPages, startPage + maxVisibleButtons - 1);

            if (endPage - startPage + 1 < maxVisibleButtons) {
                startPage = Math.max(1, endPage - maxVisibleButtons + 1);
            }

            if (startPage > 1) {
                const firstLi = document.createElement('li');
                firstLi.className = `page-item`;
                firstLi.innerHTML = `<a class="page-link shadow-sm" href="#">1</a>`;
                firstLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    renderPage(1);
                });
                paginationControls.appendChild(firstLi);

                if (startPage > 2) {
                    const dots = document.createElement('li');
                    dots.className = `page-item disabled`;
                    dots.innerHTML = `<span class="page-link shadow-sm border-0 text-muted">...</span>`;
                    paginationControls.appendChild(dots);
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link shadow-sm" href="#">${i}</a>`;
                li.addEventListener('click', (e) => {
                    e.preventDefault();
                    renderPage(i);
                });
                paginationControls.appendChild(li);
            }

            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const dots = document.createElement('li');
                    dots.className = `page-item disabled`;
                    dots.innerHTML = `<span class="page-link shadow-sm border-0 text-muted">...</span>`;
                    paginationControls.appendChild(dots);
                }
                const lastLi = document.createElement('li');
                lastLi.className = `page-item`;
                lastLi.innerHTML = `<a class="page-link shadow-sm" href="#">${totalPages}</a>`;
                lastLi.addEventListener('click', (e) => {
                    e.preventDefault();
                    renderPage(totalPages);
                });
                paginationControls.appendChild(lastLi);
            }

            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link shadow-sm rounded-end-pill" href="#">&raquo;</a>`;
            nextLi.addEventListener('click', (e) => {
                e.preventDefault();
                if (currentPage < totalPages) renderPage(currentPage + 1);
            });
            paginationControls.appendChild(nextLi);
        }

        const searchInput = document.getElementById('searchInput');
        const filterStatus = document.getElementById('filterStatus');
        const filterCurrency = document.getElementById('filterCurrency');

        window.applyFilters = function() {
            const keyword = searchInput ? searchInput.value.toLowerCase() : '';
            const statusVal = filterStatus ? filterStatus.value.toLowerCase() : '';
            const currencyVal = filterCurrency ? filterCurrency.value.toLowerCase() : '';

            filteredItems = allItems.filter(item => {
                const nama = item.getAttribute('data-nama') || '';
                const idhfm = item.getAttribute('data-idhfm') || '';
                const nowa = item.getAttribute('data-nowa') || '';
                const telegram = item.getAttribute('data-idtelegram') || '';
                const status = (item.getAttribute('data-status') || '').toLowerCase();
                const currency = (item.getAttribute('data-currency') || '').toLowerCase();

                const matchText = nama.includes(keyword) || idhfm.includes(keyword) || nowa.includes(keyword) || telegram.includes(keyword);
                const matchStatus = statusVal === '' || status === statusVal;
                const matchCurrency = currencyVal === '' || currency === currencyVal;

                return matchText && matchStatus && matchCurrency;
            });

            renderPage(1);
        };

        if (searchInput) searchInput.addEventListener('input', window.applyFilters);
        if (filterStatus) filterStatus.addEventListener('change', window.applyFilters);
        if (filterCurrency) filterCurrency.addEventListener('change', window.applyFilters);

        renderPage(1);

        const formEdit = document.getElementById('formEditMember');
        if (formEdit) {
            formEdit.addEventListener('submit', async function(e) {
                e.preventDefault();

                const btnSubmit = this.querySelector('button[type="submit"]');
                const originalText = btnSubmit.innerHTML;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
                btnSubmit.disabled = true;

                const formData = new FormData(this);

                try {
                    const targetUrl = "<?= base_url('admin/update-member-ajax') ?>";
                    const response = await fetch(targetUrl, {
                        method: 'POST',
                        body: formData
                    });

                    const res = await response.json();

                    if (res.status === 'sukses') {
                        const oldId = formData.get('old_id_hfm');
                        const newId = formData.get('id_hfm');
                        const statusVal = formData.get('status');
                        const currencyVal = formData.get('currency');
                        const depoVal = formData.get('deposit');
                        const noWaVal = formData.get('no_wa');
                        const lastTradeVal = formData.get('last_trade');

                        const row = document.querySelector(`.member-item[data-idhfm="${oldId}"]`);

                        if (row) {
                            row.setAttribute('data-idhfm', newId);
                            row.setAttribute('data-nowa', noWaVal);
                            row.setAttribute('data-currency', currencyVal);
                            row.setAttribute('data-status', statusVal);

                            const depoEl = row.querySelector('[id^="depo-"]');
                            if (depoEl) depoEl.id = 'depo-' + newId;

                            const tradeEl = row.querySelector('[id^="trade-"]');
                            if (tradeEl) tradeEl.id = 'trade-' + newId;

                            const statusElContainer = row.querySelector('[id^="status-"]');
                            if (statusElContainer) statusElContainer.id = 'status-' + newId;

                            const namaEl = row.querySelector('[id^="nama-"]');
                            if (namaEl) namaEl.id = 'nama-' + newId;

                            if (depoEl) {
                                depoEl.innerText = `${currencyVal} ${parseFloat(depoVal).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                            }

                            if (statusElContainer) {
                                if (statusVal === 'aktif') {
                                    statusElContainer.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small fw-semibold">Aktif</span>';
                                } else {
                                    statusElContainer.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill small fw-semibold">Lepas IB</span>';
                                }
                            }

                            const hfmTextEl = row.querySelector('.info-detail span:first-child');
                            if (hfmTextEl) hfmTextEl.innerHTML = `<i class="bi bi-hash text-primary"></i> ${newId}`;

                            const waTextEl = row.querySelector('.info-detail span:nth-child(2)');
                            if (waTextEl) waTextEl.innerHTML = `<i class="bi bi-whatsapp text-success"></i> ${noWaVal}`;

                            const btnEditElement = row.querySelector('.btn-edit');
                            if (btnEditElement) {
                                btnEditElement.setAttribute('data-id', newId);
                                btnEditElement.setAttribute('data-wa', noWaVal);
                                btnEditElement.setAttribute('data-currency', currencyVal);
                                btnEditElement.setAttribute('data-deposit', depoVal);
                                btnEditElement.setAttribute('data-status', statusVal);
                                btnEditElement.setAttribute('data-trade', lastTradeVal);
                            }

                            const btnSync = row.querySelector('.btn-sync-single');
                            if (btnSync) {
                                btnSync.setAttribute('onclick', `syncData(this, '${newId}')`);
                            }
                        }

                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalEdit'));
                        modal.hide();

                        window.applyFilters();

                        if (row) {
                            const btnSync = row.querySelector('.btn-sync-single');
                            if (btnSync) syncData(btnSync, newId);
                        }

                    } else {
                        alert("Gagal mengupdate: " + res.pesan);
                    }
                } catch (err) {
                    console.error("Terjadi error AJAX:", err);
                    alert("Gagal terhubung ke server.");
                } finally {
                    btnSubmit.innerHTML = originalText;
                    btnSubmit.disabled = false;
                }
            });
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        const qrModal = document.getElementById('modalQR');
        const qrCanvas = document.getElementById('qrCanvas');
        const qrContainer = document.getElementById('qrContainer');
        const botStatusBadge = document.getElementById('botStatusBadge');
        let botPollingInterval;
        const NODE_API_URL = "http://103.89.4.144:3000/api/bot-status";

        function checkBotStatus() {
            fetch(NODE_API_URL).then(res => res.json()).then(data => {
                if (data.status === "Waiting for Scan" && data.qr) {
                    qrContainer.classList.remove('d-none');
                    QRCode.toCanvas(qrCanvas, data.qr, {
                        width: 200,
                        margin: 1
                    });
                    botStatusBadge.className = "badge bg-warning text-dark p-2 px-3 rounded-pill";
                    botStatusBadge.innerHTML = "<i class='bi bi-hourglass-split me-1'></i> Menunggu Scan";
                } else if (data.status === "Connected") {
                    qrContainer.classList.add('d-none');
                    botStatusBadge.className = "badge bg-success p-2 px-3 rounded-pill";
                    botStatusBadge.innerHTML = "<i class='bi bi-check-circle-fill me-1'></i> Bot Terhubung";
                } else {
                    qrContainer.classList.add('d-none');
                    botStatusBadge.className = "badge bg-danger p-2 px-3 rounded-pill";
                    botStatusBadge.innerHTML = "<i class='bi bi-x-circle-fill me-1'></i> Terputus / Loading";
                }
            }).catch(() => {
                qrContainer.classList.add('d-none');
                botStatusBadge.className = "badge bg-secondary p-2 px-3 rounded-pill";
                botStatusBadge.innerHTML = "<i class='bi bi-wifi-off me-1'></i> Gagal Konek ke Node.js";
            });
        }
        if (qrModal) {
            qrModal.addEventListener('show.bs.modal', function() {
                checkBotStatus();
                botPollingInterval = setInterval(checkBotStatus, 3000);
            });
            qrModal.addEventListener('hide.bs.modal', function() {
                clearInterval(botPollingInterval);
            });
        }
    });

    function restartBot() {
        if (!confirm('Yakin ingin merestart bot WhatsApp?')) return;
        fetch('http://103.89.4.144:3000/api/restart-bot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: 'Bonichi#2026'
                })
            })
            .then(res => res.json()).then(data => {
                alert(data.pesan || 'Restart berhasil');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalQR'));
                if (modal) modal.hide();
            })
            .catch(err => alert('Gagal menghubungi server: ' + err.message));
    }

    function resetSesi() {
        if (!confirm('⚠️ HATI-HATI!\nIni akan MENGHAPUS sesi WhatsApp bot.\nLanjutkan?')) return;
        fetch('http://103.89.4.144:3000/api/reset-sesi', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: 'Bonichi#2026'
                })
            })
            .then(res => res.json()).then(data => {
                alert(data.pesan || 'Sesi direset');
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalQR'));
                if (modal) modal.hide();
            })
            .catch(err => alert('Gagal menghubungi server: ' + err.message));
    }
</script>
<?= $this->endSection() ?>