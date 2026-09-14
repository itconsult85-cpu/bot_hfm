<?= $this->extend('layout/template') ?>

<?= $this->section('styles') ?>
<style>
    .stat-card {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.03);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .form-control,
    .form-select {
        border-radius: 10px;
        padding: 0.6rem 1rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 mt-2 gap-3">
    <div>
        <h3 class="fw-bold tracking-tight mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Log Aktivitas Member</h3>
        <p class="text-muted small mb-0">Riwayat aktivitas masuk dan keluar member VIP.</p>
    </div>
    <a href="<?= base_url('AdminDashboard') ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
        <i class="bi bi-arrow-left me-2"></i>Kembali
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-primary-subtle text-primary me-3"><i class="bi bi-database"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Total</p>
                    <h5 class="fw-bold mb-0"><?= number_format($stats['total']) ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-success-subtle text-success me-3"><i class="bi bi-box-arrow-in-right"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Masuk</p>
                    <h5 class="fw-bold mb-0"><?= number_format($stats['masuk']) ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-danger-subtle text-danger me-3"><i class="bi bi-box-arrow-right"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Keluar</p>
                    <h5 class="fw-bold mb-0"><?= number_format($stats['keluar']) ?></h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon bg-warning-subtle text-warning-emphasis me-3"><i class="bi bi-calendar-today"></i></div>
                <div>
                    <p class="text-muted small mb-0 fw-semibold">Hari Ini</p>
                    <h5 class="fw-bold mb-0"><?= number_format($stats['hari_ini']) ?></h5>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (session()->getFlashdata('pesan')) : ?>
    <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= session()->getFlashdata('pesan') ?>
        <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="filter-section mb-4 bg-white p-3 rounded-4 shadow-sm border border-light">
    <form method="GET" action="<?= base_url('AdminDashboard/memberLogs') ?>" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-muted">Cari</label>
            <input type="text" class="form-control" name="search" placeholder="Nama, WA, atau ID..." value="<?= esc($search) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted">Tipe Aktivitas</label>
            <select class="form-select" name="tipe">
                <option value="">Semua Tipe</option>
                <?php foreach ($tipe_aktivitas_list as $t): ?>
                    <option value="<?= esc($t['tipe_aktivitas']) ?>" <?= $tipe == $t['tipe_aktivitas'] ? 'selected' : '' ?>>
                        <?= ucfirst(str_replace('_', ' ', esc($t['tipe_aktivitas']))) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted">Status Akun</label>
            <select class="form-select" name="status">
                <option value="">Semua Status</option>
                <option value="aktif" <?= ($status ?? '') == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="lepas_ib" <?= ($status ?? '') == 'lepas_ib' ? 'selected' : '' ?>>Lepas IB</option>
                <option value="keluar_grup" <?= ($status ?? '') == 'keluar_grup' ? 'selected' : '' ?>>Keluar Grup</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted">Dari</label>
            <input type="date" class="form-control" name="date_from" value="<?= esc($date_from) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold text-muted">Sampai</label>
            <input type="date" class="form-control" name="date_to" value="<?= esc($date_to) ?>">
        </div>
        <div class="col-md-1">
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i></button>
        </div>
    </form>
</div>

<div class="table-container shadow-sm bg-white rounded-4 border border-light overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;" class="text-center border-bottom-0">#</th>
                    <th style="width: 20%;" class="border-bottom-0">Nama</th>
                    <th style="width: 15%;" class="border-bottom-0">Kontak (WA & Tele)</th>
                    <th style="width: 15%;" class="border-bottom-0">ID Trading</th>
                    <th style="width: 15%;" class="border-bottom-0">Tipe Aktivitas</th>
                    <th style="width: 15%;" class="border-bottom-0">Status</th>
                    <th style="width: 15%;" class="border-bottom-0">Waktu</th>
                    <th style="width: 80px;" class="text-center border-bottom-0">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3">Tidak ada data</h6>
                                <p class="text-muted small">Belum ada log aktivitas yang tercatat.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = ($currentPage - 1) * $perPage + 1; ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-muted small fw-semibold text-center"><?= $no++ ?></td>
                            <td><span class="fw-semibold text-dark"><?= esc($log['nama']) ?></span></td>

                            <td>
                                <div class="text-muted small"><i class="bi bi-whatsapp text-success me-1"></i><?= esc($log['no_wa']) ?></div>
                                <div class="text-muted small mt-1"><i class="bi bi-telegram text-info me-1"></i><?= esc($log['id_telegram'] ?? '-') ?></div>
                            </td>

                            <td>
                                <?php if (!empty($log['id_hfm'])): ?>
                                    <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1"><?= esc($log['id_hfm']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $tipeAktivitasBersih = trim($log['tipe_aktivitas']);
                                $badgeClass = 'bg-secondary';
                                $icon = 'bi-circle';
                                switch ($tipeAktivitasBersih) {
                                    case 'masuk':
                                    case 'masuk_id_baru':
                                        $badgeClass = 'bg-success text-white';
                                        $icon = 'bi-box-arrow-in-right';
                                        break;
                                    case 'aktif_kembali':
                                        $badgeClass = 'bg-success text-white';
                                        $icon = 'bi-check-circle-fill';
                                        break;
                                    case 'keluar_sendiri':
                                        $badgeClass = 'bg-danger text-white';
                                        $icon = 'bi-box-arrow-right';
                                        break;
                                    case 'keluar_di_remove':
                                    case 'dihapus_admin_web':
                                        $badgeClass = 'bg-danger text-white';
                                        $icon = 'bi-x-circle';
                                        break;
                                    case 'lepas_ib':
                                        $badgeClass = 'bg-warning text-dark';
                                        $icon = 'bi-exclamation-triangle';
                                        break;
                                }
                                $label = match ($tipeAktivitasBersih) {
                                    'keluar_sendiri' => 'Keluar sendiri',
                                    'keluar_di_remove', 'dihapus_admin_web' => 'Di-kick admin',
                                    'keluar_grup' => 'Keluar grup',
                                    default => ucfirst(str_replace('_', ' ', $tipeAktivitasBersih)),
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><i class="bi <?= $icon ?> me-1"></i> <?= $label ?></span>
                            </td>
                            <td>
                                <?php
                                $statusMember = $log['status_member'] ?? '';
                                if ($statusMember == 'aktif'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Aktif</span>
                                <?php elseif ($statusMember == 'lepas_ib'): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill">Lepas IB</span>
                                <?php elseif ($statusMember == 'keluar_grup'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Keluar Grup</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Tidak Terdaftar</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="text-muted small" title="<?= esc($log['created_at']) ?>"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($log['created_at'])) ?><br><i class="bi bi-clock me-1"></i><?= date('H:i:s', strtotime($log['created_at'])) ?> WIB</span></td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    <button class="btn btn-sm btn-light border rounded-circle" onclick="editIdLog('<?= esc($log['id_hfm'] ?? '') ?>')" title="Edit ID Trading"><i class="bi bi-pencil-square text-warning"></i></button>

                                    <button class="btn btn-sm btn-light border rounded-circle" onclick="showDetail('<?= esc($log['no_wa']) ?>', '<?= esc($log['id_telegram'] ?? '-') ?>', '<?= esc($log['nama']) ?>', '<?= esc($log['tipe_aktivitas']) ?>', '<?= esc($log['created_at']) ?>', '<?= esc($log['id_hfm'] ?? '-') ?>')"><i class="bi bi-eye text-primary"></i></button>
                                    <a href="<?= base_url('AdminDashboard/hapusLog/' . esc($log['id'] ?? '')) ?>" class="btn btn-sm btn-light border rounded-circle" data-confirm="Yakin ingin menghapus riwayat log ini?" data-confirm-title="Hapus riwayat log" data-confirm-button="Ya, hapus"><i class="bi bi-trash text-danger"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4">
            <span class="text-muted small mb-2 mb-md-0">
                Menampilkan <b><?= ($currentPage - 1) * $perPage + 1 ?></b> hingga <b><?= min($currentPage * $perPage, $totalData) ?></b> dari <b><?= number_format($totalData) ?></b> data
            </span>
            <nav>
                <ul class="pagination pagination-sm mb-0 flex-wrap">
                    
                    <!-- 1. Tombol Previous -->
                    <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                        <a class="page-link shadow-sm rounded-start-pill" href="<?= $currentPage > 1 ? '?page='.($currentPage - 1).'&search='.urlencode($search).'&tipe='.urlencode($tipe).'&status='.urlencode($status ?? '').'&date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '#' ?>">&laquo;</a>
                    </li>

                    <?php
                    // LOGIKA PEMBATASAN HALAMAN (Maksimal 5 tombol di tengah)
                    $maxVisibleButtons = 5;
                    $startPage = max(1, $currentPage - floor($maxVisibleButtons / 2));
                    $endPage = min($totalPages, $startPage + $maxVisibleButtons - 1);

                    // Penyesuaian jika ada di ujung akhir halaman
                    if ($endPage - $startPage + 1 < $maxVisibleButtons) {
                        $startPage = max(1, $endPage - $maxVisibleButtons + 1);
                    }
                    ?>

                    <!-- 2. Halaman Pertama & Titik-titik (Ellipsis) -->
                    <?php if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link shadow-sm" href="?page=1&search=<?= urlencode($search) ?>&tipe=<?= urlencode($tipe) ?>&status=<?= urlencode($status ?? '') ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link shadow-sm border-0 text-muted">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- 3. Render Tombol Angka Utama -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                            <a class="page-link shadow-sm" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&tipe=<?= urlencode($tipe) ?>&status=<?= urlencode($status ?? '') ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <!-- 4. Tampilkan Titik-titik & Halaman Terakhir -->
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link shadow-sm border-0 text-muted">...</span>
                            </li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link shadow-sm" href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&tipe=<?= urlencode($tipe) ?>&status=<?= urlencode($status ?? '') ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- 5. Tombol Next -->
                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link shadow-sm rounded-end-pill" href="<?= $currentPage < $totalPages ? '?page='.($currentPage + 1).'&search='.urlencode($search).'&tipe='.urlencode($tipe).'&status='.urlencode($status ?? '').'&date_from='.urlencode($date_from).'&date_to='.urlencode($date_to) : '#' ?>">&raquo;</a>
                    </li>
                    
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold mb-0"><i class="bi bi-info-circle text-primary me-2"></i>Detail Aktivitas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="text-muted small fw-semibold d-block mb-1">Nama</label>
                    <p class="fw-semibold mb-0" id="detailNama">-</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small fw-semibold d-block mb-1">Nomor WA</label>
                    <p class="fw-semibold mb-0" id="detailWa">-</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small fw-semibold d-block mb-1">ID Telegram</label>
                    <p class="fw-semibold text-info mb-0" id="detailTelegram">-</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small fw-semibold d-block mb-1">ID Trading</label>
                    <p class="fw-bold text-primary mb-0" id="detailIdTrading">-</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small fw-semibold d-block mb-1">Tipe Aktivitas</label>
                    <p class="fw-semibold mb-0" id="detailTipe">-</p>
                </div>
                <div>
                    <label class="text-muted small fw-semibold d-block mb-1">Waktu</label>
                    <p class="fw-semibold mb-0" id="detailWaktu">-</p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditId" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold mb-0"><i class="bi bi-pencil-square text-warning me-2"></i>Edit ID Trading</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url('admin/update-id-quick') ?>" method="POST">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Perbaiki ID Trading yang salah secara langsung.</p>
                    <input type="hidden" id="quick_old_id" name="old_id_hfm">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Ubah menjadi ID Asli:</label>
                        <input type="text" class="form-control" id="quick_new_id" name="id_hfm" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-3 fw-semibold">Simpan ID</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function showDetail(wa, telegram, nama, tipe, waktu, idTrading) {
        document.getElementById('detailWa').textContent = wa;
        document.getElementById('detailTelegram').textContent = telegram;
        document.getElementById('detailNama').textContent = nama;
        document.getElementById('detailIdTrading').textContent = idTrading;

        const tipeMap = {
            'masuk': 'Masuk',
            'keluar_sendiri': 'Keluar (Sendiri)',
            'keluar_di_remove': 'Keluar (Di-Remove)',
            'lepas_ib': 'Lepas IB'
        };
        document.getElementById('detailTipe').textContent = tipeMap[tipe] || tipe;

        const date = new Date(waktu);
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        document.getElementById('detailWaktu').textContent = date.toLocaleDateString('id-ID', options);

        new bootstrap.Modal(document.getElementById('modalDetail')).show();
    }

    function editIdLog(idHfm) {
        if (!idHfm || idHfm === '-') {
            alert('Tidak ada ID Trading yang terdeteksi untuk diedit.');
            return;
        }
        document.getElementById('quick_old_id').value = idHfm;
        document.getElementById('quick_new_id').value = idHfm;

        new bootstrap.Modal(document.getElementById('modalEditId')).show();
    }

    document.querySelectorAll('.form-control, .form-select').forEach(el => {
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                this.closest('form').submit();
            }
        });
    });
</script>
<?= $this->endSection() ?>
