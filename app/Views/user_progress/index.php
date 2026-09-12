<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-3">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold tracking-tight mb-1">
                <i class="bi bi-person-lines-fill text-primary me-2"></i><?= esc($title ?? 'Progress Pendaftar') ?>
            </h3>
            <p class="text-muted small mb-0">Pantau tahap pendaftaran member secara realtime dari bot Telegram.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('user-progress') ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold shadow-sm">
                <i class="bi bi-arrow-clockwise me-1"></i> Reset Filter
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('pesan')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 py-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('pesan') ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4 p-3 mb-4 bg-white">
        <form method="GET" action="<?= base_url('user-progress') ?>" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 rounded-start-pill ps-3"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 bg-light rounded-end-pill" name="search" placeholder="Cari berdasarkan Nama atau ID Telegram..." value="<?= esc($search ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select rounded-pill bg-light" name="step">
                    <option value="">Semua Tahap (Step)</option>
                    <?php for ($i = 1; $i <= 7; $i++): ?>
                        <option value="<?= $i ?>" <?= (isset($step_filter) && $step_filter == $i) ? 'selected' : '' ?>>Step <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm flex-grow-1">
                    <i class="bi bi-filter me-1"></i> Filter Data
                </button>
                <?php if (!empty($search) || !empty($step_filter)): ?>
                    <a href="<?= base_url('user-progress') ?>" class="btn btn-light border rounded-pill px-3 text-danger fw-semibold" title="Hapus Filter">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="py-3 border-bottom-0 ps-4">Telegram ID</th>
                        <th class="py-3 border-bottom-0">Nama User</th>
                        <th class="py-3 border-bottom-0 text-center">Tahap Saat Ini</th>
                        <th class="py-3 border-bottom-0 text-center">Terakhir Aktif</th>
                        <th class="py-3 border-bottom-0 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($progress)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                                    <h6 class="fw-bold mt-3">Belum Ada Data Progress</h6>
                                    <p class="text-muted small">Tidak ada data yang cocok dengan kriteria pencarian.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($progress as $p): ?>
                            <tr>
                                <td class="fw-semibold ps-4 text-secondary">
                                    <i class="bi bi-telegram text-primary me-1"></i> <?= esc($p['user_id']) ?>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= esc($p['user_name'] ?? 'Tanpa Nama') ?></span>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $step = (int)$p['current_step'];
                                    $badgeColor = 'bg-secondary';
                                    if ($step == 7) $badgeColor = 'bg-success';
                                    elseif ($step >= 4) $badgeColor = 'bg-info text-dark';
                                    elseif ($step >= 2) $badgeColor = 'bg-primary';
                                    ?>
                                    <span class="badge <?= $badgeColor ?> rounded-pill px-3 py-2 shadow-sm">
                                        Step <?= esc($p['current_step']) ?>
                                    </span>
                                </td>
                                <td class="text-center text-muted small">
                                    <i class="bi bi-clock me-1"></i> <?= date('d M Y, H:i', strtotime($p['last_active'])) ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('user-progress/delete/' . $p['user_id']) ?>" class="btn btn-sm btn-light border text-danger rounded-pill px-3 fw-semibold shadow-sm" onclick="return confirm('Yakin ingin mereset riwayat user ini kembali ke Step 1?')">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PAGER / PAGINATION SECTION (GAYA BOSSCUAN) -->
    <?php if (isset($pager) && $pager->getPageCount('default') > 1): ?>
        <?php
        // Ambil info halaman dari sistem CodeIgniter
        $currentPage = $pager->getCurrentPage('default');
        $totalPages  = $pager->getPageCount('default');
        $totalData   = $pager->getTotal('default');
        $perPage     = $pager->getPerPage('default') ?? 10;

        // Siapkan parameter URL agar filter (Pencarian & Step) tidak hilang saat pindah halaman
        $qs = [];
        if (!empty($search)) $qs['search'] = $search;
        if (!empty($step_filter)) $qs['step'] = $step_filter;
        $queryStr = !empty($qs) ? '&' . http_build_query($qs) : '';
        ?>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4">
            <span class="text-muted small mb-2 mb-md-0">
                Menampilkan <b><?= ($currentPage - 1) * $perPage + 1 ?></b> hingga <b><?= min($currentPage * $perPage, $totalData) ?></b> dari <b><?= number_format($totalData) ?></b> data
            </span>
            <nav>
                <ul class="pagination pagination-sm mb-0 flex-wrap">

                    <!-- 1. Tombol Previous -->
                    <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                        <a class="page-link shadow-sm rounded-start-pill" href="<?= $currentPage > 1 ? '?page=' . ($currentPage - 1) . $queryStr : '#' ?>">&laquo;</a>
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
                            <a class="page-link shadow-sm" href="?page=1<?= $queryStr ?>">1</a>
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
                            <a class="page-link shadow-sm" href="?page=<?= $i ?><?= $queryStr ?>"><?= $i ?></a>
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
                            <a class="page-link shadow-sm" href="?page=<?= $totalPages ?><?= $queryStr ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- 5. Tombol Next -->
                    <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link shadow-sm rounded-end-pill" href="<?= $currentPage < $totalPages ? '?page=' . ($currentPage + 1) . $queryStr : '#' ?>">&raquo;</a>
                    </li>

                </ul>
            </nav>
        </div>
    <?php endif; ?>

</div>
<?= $this->endSection() ?>