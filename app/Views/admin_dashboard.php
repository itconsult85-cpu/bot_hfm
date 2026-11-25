<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 mt-2 gap-3">
    <div>
        <h3 class="fw-bold tracking-tight mb-1">Overview Realtime</h3>
        <p class="text-muted small mb-0">Pantau performa pendaftaran dan aktivitas grup VIP Anda.</p>
    </div>
    <div class="d-flex flex-column flex-sm-row gap-2">
        <button type="button" class="btn btn-warning rounded-pill px-4 fw-semibold shadow-sm"
            onclick="restartTelegramBot()">
            <i class="bi bi-arrow-clockwise me-2"></i>Restart Bot Telegram
        </button>
        <a href="<?= base_url('AdminDashboard/backupDatabase') ?>"
            class="btn btn-outline-dark rounded-pill px-4 fw-semibold shadow-sm">
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
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show rounded-4 px-4 py-3 mb-4 shadow-sm" role="alert">
    <i class="bi bi-exclamation-circle-fill me-2"></i><?= esc(session()->getFlashdata('error')) ?>
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
                    <h3 class="fw-bold mb-0 text-dark"><?= $aktif_total ?> <span
                            class="fs-6 text-muted fw-normal">User</span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center">
                <div class="metric-icon bg-success-subtle text-success me-3"><i class="bi bi-person-plus-fill"></i>
                </div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Daftar Baru (Hari Ini)</p>
                    <h3 class="fw-bold mb-0 text-dark"><?= $baru_hari_ini ?> <span
                            class="fs-6 text-muted fw-normal">User</span></h3>
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
                    <h3 class="fw-bold mb-0 text-dark"><?= $lepas_hari_ini ?> <span
                            class="fs-6 text-muted fw-normal">User</span></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 col-12">
        <div class="card metric-card p-4">
            <div class="d-flex align-items-center">
                <div class="metric-icon bg-warning-subtle text-warning-emphasis me-3"><i
                        class="bi bi-clock-history"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Pasif > 30 Hari</p>
                    <h3 class="fw-bold mb-0 text-dark"><?= $pasif_total ?> <span
                            class="fs-6 text-muted fw-normal">User</span></h3>
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
    <div class="d-flex flex-column flex-md-row gap-2 member-filter-controls">
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
        <input type="text" id="searchInput" class="form-control rounded-pill px-3"
            placeholder="Cari Nama, ID, WA, Tele, email, campaign...">
        <button class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm text-nowrap" onclick="syncSemua()"
            id="btnSyncSemua">
            <i class="bi bi-cloud-arrow-down-fill me-2"></i> Sync Semua
        </button>
    </div>
</div>

<div class="table-responsive rounded-4 border bg-white shadow-sm dashboard-member-table-wrap">
    <table id="membersDataTable" class="table table-hover align-middle mb-0 w-100 dashboard-member-table">
        <thead>
            <tr>
                <th class="text-center">Identitas Member</th>
                <th class="text-end">Deposit</th>
                <th class="text-end">Volume</th>
                <th class="text-end">Withdrawals</th>
                <th class="text-center">Terakhir Trading</th>
                <th class="text-center">Status IB</th>
                <th class="text-center">Akun</th>
                <th>Campaign</th>
                <th class="text-center">Tanggal Daftar</th>
                <th class="text-center">Aksi</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold mb-0">Edit Data Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditMember">
                <?= csrf_field() ?>
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
                            <input type="number" step="0.01" class="form-control" id="edit_deposit" name="deposit"
                                required>
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
                            <input type="text" class="form-control" id="edit_trade" name="last_trade"
                                placeholder="YYYY-MM-DD HH:MM:SS">
                            <small class="text-muted" style="font-size: 0.7rem;">Isi 0000-00-00 00:00:00 jika belum
                                trade</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Simpan
                        Perubahan</button>
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
                    <button type="button" class="btn btn-link text-muted btn-sm text-decoration-none"
                        data-bs-dismiss="modal">Batalkan</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalQR" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg text-center p-4">
            <h5 class="fw-bold mb-3">Koneksi Bot Telegram</h5>
            <div id="qrContainer" class="mb-3 d-none">
                <canvas id="qrCanvas" class="img-fluid rounded shadow-sm border p-2"></canvas>
                <p class="text-muted small mt-2">Pindai kode QR ini untuk menghubungkan bot.</p>
            </div>
            <div id="statusContainer" class="p-3 rounded bg-light mb-0">
                <span id="botStatusBadge" class="badge bg-secondary p-2 px-3 rounded-pill">Mengecek status...</span>
            </div>
            <div class="d-grid gap-2 mt-3">
                <button class="btn btn-warning rounded-pill" onclick="restartBot()"><i
                        class="bi bi-arrow-clockwise me-1"></i> Restart Bot</button>
                <button class="btn btn-danger rounded-pill" onclick="resetSesi()"><i class="bi bi-trash me-1"></i> Reset
                    Sesi (Ganti Nomor)</button>
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/vendor/qrcode/qrcode.min.js') ?>"></script>
<script>
document.addEventListener('click', function(e) {
    const deleteButton = e.target.closest('.btn-hapus');
    if (deleteButton) {
        e.preventDefault();
        document.getElementById('teksIdHapus').innerText = deleteButton.dataset.id || '';
        document.getElementById('teksNamaHapus').innerText = deleteButton.dataset.nama || 'Member BOSSCUAN';
        document.getElementById('linkHapusModal').href = "<?= base_url('AdminDashboard/hapus-member/') ?>" + (
            deleteButton.dataset.id || '');
    }
    const editButton = e.target.closest('.btn-edit');
    if (editButton) {
        document.getElementById('edit_id').value = editButton.dataset.id || '';
        document.getElementById('edit_old_id').value = editButton.dataset.id || '';
        document.getElementById('edit_wa').value = editButton.dataset.wa || '';
        document.getElementById('edit_currency').value = editButton.dataset.currency || 'USD';
        document.getElementById('edit_deposit').value = editButton.dataset.deposit || '';
        document.getElementById('edit_status').value = editButton.dataset.status || 'aktif';
        document.getElementById('edit_trade').value = editButton.dataset.trade || '';
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }
});
async function syncData(btn, id_hfm, isBulk = false) {
    let syncSucceeded = false;
    try {
        btn.innerHTML = '<i class="bi bi-arrow-repeat sync-icon-spin" aria-hidden="true"></i>';
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
            syncSucceeded = true;
            let currency = (res.data.currency && res.data.currency !== '') ? res.data.currency : 'USD';
            let depoVal = parseFloat(res.data.deposit);
            let depoFormatted = currency === 'IDR' ? currency + ' ' + depoVal.toLocaleString('id-ID') : currency +
                ' ' + depoVal.toFixed(2);

            let depoEl = document.getElementById('depo-' + id_hfm);
            if (depoEl) depoEl.innerText = depoFormatted;

            if (Object.prototype.hasOwnProperty.call(res.data, 'withdrawals')) {
                const withdrawalsEl = document.getElementById('withdrawals-' + id_hfm);
                if (withdrawalsEl) {
                    const withdrawalsRaw = res.data.withdrawals;
                    const withdrawalsValue = Number(withdrawalsRaw);
                    withdrawalsEl.innerText = withdrawalsRaw !== null && withdrawalsRaw !== '' && Number.isFinite(
                            withdrawalsValue) ?
                        currency + ' ' + withdrawalsValue.toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }) :
                        'Belum tersinkron';
                }
            }

            if (Object.prototype.hasOwnProperty.call(res.data, 'volume')) {
                const volumeEl = document.getElementById('volume-' + id_hfm);
                if (volumeEl) {
                    const volumeValue = Number(res.data.volume);
                    volumeEl.innerText = res.data.volume !== null && res.data.volume !== '' && Number.isFinite(
                            volumeValue) ?
                        volumeValue.toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }) :
                        'Belum tersinkron';
                }
            }

            if (res.data.nama) {
                let elemNama = document.getElementById('nama-' + id_hfm);
                if (elemNama) elemNama.innerText = res.data.nama;
            }

            if (Object.prototype.hasOwnProperty.call(res.data, 'email')) {
                const emailEl = document.getElementById('email-' + id_hfm);
                if (emailEl) emailEl.innerText = res.data.email || '-';
            }
            if (Object.prototype.hasOwnProperty.call(res.data, 'campaign')) {
                const campaignEl = document.getElementById('campaign-' + id_hfm);
                if (campaignEl) campaignEl.innerText = res.data.campaign || '-';
            }

            let rawTrade = res.data.last_trade;
            let tradeEl = document.getElementById('trade-' + id_hfm);
            if (tradeEl) {
                if (rawTrade && rawTrade !== '0000-00-00 00:00:00' && rawTrade !== 'N/A') {
                    if (rawTrade.includes('-')) {
                        let parts = rawTrade.split(' ')[0].split('-');
                        let bulanIndo = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt',
                            'Nov', 'Des'
                        ];
                        let tgl = parseInt(parts[2]) + ' ' + bulanIndo[parseInt(parts[1]) - 1] + ' ' + parts[0];
                        tradeEl.innerHTML =
                            `<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill fw-semibold small"><i class="bi bi-graph-up me-1"></i>${tgl}</span>`;
                    }
                } else {
                    tradeEl.innerHTML =
                        `<span class="badge bg-light border text-muted rounded-pill small">Belum Trading</span>`;
                }
            }

            let statusEl = document.getElementById('status-' + id_hfm);
            if (statusEl) statusEl.innerHTML =
                '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 small fw-semibold">Aktif</span>';

            const row = document.querySelector(`.member-item[data-idhfm="${id_hfm}"]`);
            if (row) {
                row.setAttribute('data-status', 'aktif');
                if (Object.prototype.hasOwnProperty.call(res.data, 'email')) row.setAttribute('data-email', String(
                    res.data.email || '').toLowerCase());
                if (Object.prototype.hasOwnProperty.call(res.data, 'campaign')) row.setAttribute('data-campaign',
                    String(res.data.campaign || '').toLowerCase());
            }

        } else if (res.status === 'lepas_ib') {
            syncSucceeded = true;
            const row = document.querySelector(`.member-item[data-idhfm="${id_hfm}"]`);
            const statusEl = document.getElementById('status-' + id_hfm);
            if (statusEl) statusEl.innerHTML =
                '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 small fw-semibold">Lepas IB</span>';
            if (row) {
                row.setAttribute('data-status', 'lepas_ib');
            }

        } else {
            alert("Gagal Sync: " + (res.pesan || "Terjadi kesalahan sistem"));
        }

        if (!isBulk && typeof window.applyFilters === 'function') window.applyFilters();

    } catch (err) {
        console.error('Terjadi Error di JS:', err);
    } finally {
        btn.disabled = false;
        btn.classList.remove('btn-light', 'btn-outline-primary', 'btn-outline-success');
        btn.classList.add('btn-outline-success');
        btn.innerHTML = syncSucceeded ?
            '<i class="bi bi-check-lg" aria-hidden="true"></i>' :
            '<i class="bi bi-arrow-repeat" aria-hidden="true"></i>';
        btn.title = syncSucceeded ? 'Sinkronisasi berhasil' : 'Coba sinkronisasi lagi';
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

    btnSemua.innerHTML = '<i class="bi bi-arrow-repeat sync-icon-spin" aria-hidden="true"></i>';
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

    btnSemua.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i>';
    btnSemua.classList.replace('btn-primary', 'btn-success');

    setTimeout(() => {
        btnSemua.innerHTML = '<i class="bi bi-cloud-arrow-down-fill me-2"></i> Sync Semua';
        btnSemua.classList.replace('btn-success', 'btn-primary');
        btnSemua.disabled = false;
    }, 3000);
}

document.addEventListener("DOMContentLoaded", function() {
    const esc = value => $('<div>').text(value ?? '').html();
    const money = (value, currency) => {
        if (value === null || value === '' || value === undefined || Number.isNaN(Number(value)))
            return 'Belum tersinkron';
        return `${esc(currency || 'USD')} ${Number(value).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    };
    const dateLabel = value => {
        if (!value || value === '0000-00-00 00:00:00')
            return '<span class="badge bg-light border text-muted rounded-pill">Belum Trading</span>';
        const date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? esc(value) :
            `<span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill"><i class="bi bi-graph-up me-1"></i>${date.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'})}</span>`;
    };
    const registrationDateLabel = value => {
        if (!value) return '<span class="text-muted">-</span>';
        const date = new Date(String(value).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? esc(value) :
            `<span class="text-muted"><i class="bi bi-calendar-event me-1"></i>${date.toLocaleDateString('id-ID', {day: '2-digit', month: 'short', year: 'numeric'})}</span>`;
    };
    const table = $('#membersDataTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100],
            [10, 25, 50, 100]
        ],
        order: [
            [8, 'desc']
        ],
        searching: true,
        dom: '<"row mb-3 align-items-center"<"col-md-6"l><"col-md-6 text-md-end">>rt<"row mt-3 align-items-center"<"col-md-6"i><"col-md-6"p>>',
        language: {
            url: "<?= base_url('assets/vendor/datatables/i18n/id.json') ?>",
            search: 'Cari:'
        },
        ajax: {
            url: "<?= base_url('AdminDashboard/membersDataTables') ?>",
            data: function(d) {
                d.status = $('#filterStatus').val() || '';
                d.currency = $('#filterCurrency').val() || '';
            }
        },
        columns: [{
                data: null,
                className: 'text-center',
                render: row =>
                    `<div class="member-identity-stack"><div class="member-identity-name">${esc(row.nama || 'Member BOSSCUAN')}</div><div><span class="member-identity-label">ID HFM</span><span class="member-identity-value">${esc(row.id_hfm)}</span></div><div><span class="member-identity-label">WHATSAPP</span><span class="member-identity-value">${esc(row.no_wa || '-')}</span></div><div><span class="member-identity-label">TELEGRAM</span><span class="member-identity-value">${esc(row.id_telegram || '-')}</span></div><div><span class="member-identity-label">EMAIL</span><span class="member-identity-value">${esc(row.email || '-')}</span></div></div>`
            },
            {
                data: null,
                className: 'text-end text-nowrap',
                render: row => money(row.deposit, row.currency)
            },
            {
                data: null,
                className: 'text-end text-nowrap',
                render: row => money(row.withdrawals, row.currency)
            },
            {
                data: 'volume',
                className: 'text-end text-nowrap',
                render: (data, type, row) =>
                    `<span id="volume-${esc(row.id_hfm)}">${data === null || data === '' || Number.isNaN(Number(data)) ? '<span class="text-muted">Belum tersinkron</span>' : Number(data).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`
            },
            {
                data: 'last_trade',
                className: 'text-center text-nowrap',
                render: data => dateLabel(data)
            },
            {
                data: 'status',
                className: 'text-center',
                render: data => data === 'aktif' ?
                    '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Aktif</span>' :
                    '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Lepas IB</span>'
            },
            {
                data: 'currency',
                className: 'text-center',
                render: data =>
                    `<span class="badge bg-primary-subtle text-primary rounded-pill">${esc(data || 'USD')}</span>`
            },
            {
                data: 'campaign',
                render: data => `<span class="text-break">${esc(data || '-')}</span>`
            },
            {
                data: 'created_at',
                className: 'text-center text-nowrap',
                render: data => registrationDateLabel(data)
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                render: row => {
                    const id = esc(row.id_hfm),
                        name = esc(row.nama || 'Member BOSSCUAN');
                    const invite = row.status === 'aktif' && /^[1-9][0-9]{4,19}$/.test(String(
                            row.id_telegram || '')) ?
                        `<form method="post" action="<?= base_url('AdminDashboard/resendTelegramInvite') ?>" class="d-inline"><input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>"><input type="hidden" name="id_hfm" value="${id}"><button type="submit" class="btn btn-outline-info btn-sm" title="Kirim ulang invite"><i class="bi bi-send"></i></button></form>` :
                        '';
                    return `<div class="d-flex gap-1 flex-wrap"><button class="btn btn-outline-warning btn-sm btn-edit" data-id="${id}" data-wa="${esc(row.no_wa)}" data-currency="${esc(row.currency || 'USD')}" data-deposit="${esc(row.deposit)}" data-status="${esc(row.status)}" data-trade="${esc(row.last_trade || '')}" title="Edit"><i class="bi bi-pencil-square"></i></button><button class="btn btn-outline-primary btn-sm btn-sync-single" onclick="syncData(this, '${id}')" title="Sinkronisasi data"><i class="bi bi-arrow-repeat"></i></button>${invite}<button class="btn btn-outline-danger btn-sm btn-hapus" data-id="${id}" data-nama="${name}" data-bs-toggle="modal" data-bs-target="#modalHapus" title="Hapus"><i class="bi bi-trash3"></i></button></div>`;
                }
            }
        ],
        createdRow: function(row, data) {
            const labels = ['Identitas Member', 'Deposit', 'Volume', 'Withdrawals',
                'Terakhir Trading', 'Status IB', 'Akun', 'Campaign', 'Tanggal Daftar', 'Aksi'
            ];
            $(row).addClass('member-item').attr({
                'data-idhfm': data.id_hfm,
                'data-status': data.status,
                'data-currency': data.currency || 'USD'
            });
            $(row).children('td').each(function(index) {
                $(this).attr('data-label', labels[index] || '');
            });
        }
    });
    window.memberTable = table;
    window.getFilteredItems = () => table.rows({
        search: 'applied'
    }).nodes().toArray();
    window.applyFilters = () => table.ajax.reload(null, true);
    $('#filterStatus, #filterCurrency').on('change', () => table.ajax.reload());
    $('#searchInput').on('input', function() {
        table.search(this.value).draw();
    });
    const formEdit = document.getElementById('formEditMember');
    if (formEdit) {
        formEdit.addEventListener('submit', async function(e) {
            e.preventDefault();

            const btnSubmit = this.querySelector('button[type="submit"]');
            const originalText = btnSubmit.innerHTML;
            btnSubmit.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
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
                            depoEl.innerText =
                                `${currencyVal} ${parseFloat(depoVal).toLocaleString('en-US', {minimumFractionDigits: 2})}`;
                        }

                        if (statusElContainer) {
                            if (statusVal === 'aktif') {
                                statusElContainer.innerHTML =
                                    '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 small fw-semibold">Aktif</span>';
                            } else {
                                statusElContainer.innerHTML =
                                    '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 small fw-semibold">Lepas IB</span>';
                            }
                        }

                        const hfmTextEl = row.querySelector(
                            '.text-muted.small.mt-1 div:nth-child(1)');
                        if (hfmTextEl) hfmTextEl.innerHTML =
                            `<i class="bi bi-hash text-primary"></i> ${newId}`;

                        const waTextEl = row.querySelector(
                            '.text-muted.small.mt-1 div:nth-child(2)');
                        if (waTextEl) {
                            // Karena sekarang WA dan Telegram beda baris (div), kita hanya update teks WA-nya saja
                            waTextEl.innerHTML =
                                `<i class="bi bi-whatsapp text-success"></i> ${noWaVal}`;
                        }

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
                botStatusBadge.innerHTML =
                    "<i class='bi bi-x-circle-fill me-1'></i> Bot terputus / belum siap";
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

async function restartBot() {
    if (!await showAppConfirm('Yakin ingin merestart bot WhatsApp?', {
            title: 'Restart bot WhatsApp',
            confirmText: 'Restart sekarang'
        })) return;
    fetch('http://103.89.4.144:3000/api/restart-bot', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: ''
            })
        })
        .then(res => res.json()).then(data => {
            alert(data.pesan || 'Restart berhasil');
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalQR'));
            if (modal) modal.hide();
        })
        .catch(err => alert('Gagal menghubungi server: ' + err.message));
}

async function restartTelegramBot() {
    const approved = await showAppConfirm(
        'Restart proses PM2 bot_tele_hfm sekarang? Bot akan berhenti sebentar lalu dijalankan kembali oleh PM2.', {
            title: 'Restart bot Telegram',
            confirmText: 'Restart sekarang'
        });
    if (!approved) return;

    try {
        const response = await fetch('<?= base_url('bot-schedules/restart-bot') ?>', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
                'Accept': 'application/json'
            }
        });
        const raw = await response.text();
        let data;
        try {
            data = JSON.parse(raw);
        } catch (_) {
            data = {
                error: raw.substring(0, 500)
            };
        }
        if (!response.ok) {
            const detail = data.detail ? `\nDetail: ${data.detail}` : '';
            throw new Error((data.error || `HTTP ${response.status}`) + detail);
        }
        await showAppAlert(data.message || 'Permintaan restart bot Telegram berhasil dikirim.');
    } catch (error) {
        await showAppAlert('Restart bot Telegram gagal: ' + error.message);
    }
}

async function resetSesi() {
    if (!await showAppConfirm('HATI-HATI!\nIni akan MENGHAPUS sesi WhatsApp bot.\nLanjutkan?', {
            title: 'Reset sesi WhatsApp',
            confirmText: 'Ya, reset sesi',
            danger: true
        })) return;
    fetch('http://103.89.4.144:3000/api/reset-sesi', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: ''
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
