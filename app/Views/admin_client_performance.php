<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold tracking-tight text-primary mb-1">
                <i class="bi bi-graph-up me-2"></i><?= esc($title) ?>
            </h4>
            <p class="text-muted small mb-0">Laporan kinerja klien berdasarkan API HFM.</p>
        </div>
    </div>

    <!-- Filter Data -->
    <div class="bg-light p-3 rounded-4 border mb-4">
        <form method="GET" action="<?= base_url('AdminDashboard/clientPerformance') ?>" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Dari Tanggal</label>
                <input type="date" class="form-control form-control-sm" name="from_date" value="<?= esc($date_from) ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted">Sampai Tanggal</label>
                <input type="date" class="form-control form-control-sm" name="to_date" value="<?= esc($date_to) ?>">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted">Status</label>
                <select class="form-select form-select-sm" name="activity_status">
                    <option value="">Semua Status</option>
                    <option value="active" <?= $activity_status == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $activity_status == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small text-muted">Platform</label>
                <select class="form-select form-select-sm" name="platform">
                    <option value="">Semua</option>
                    <option value="MT4" <?= $platform == 'MT4' ? 'selected' : '' ?>>MT4</option>
                    <option value="MT5" <?= $platform == 'MT5' ? 'selected' : '' ?>>MT5</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold"><i class="bi bi-filter"></i> Filter</button>
            </div>
        </form>
    </div>

    <?php if ($debug_api): ?>
        <div class="alert alert-warning small py-2"><i class="bi bi-exclamation-triangle me-2"></i><?= esc($debug_api) ?></div>
    <?php endif; ?>

    <!-- Summary (Totals) -->
    <?php if (!empty($totals)): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded shadow-sm text-center"><small class="text-muted d-block">Clients</small><span class="fw-bold"><?= $totals['clients'] ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded shadow-sm text-center"><small class="text-muted d-block">Accounts</small><span class="fw-bold"><?= $totals['accounts'] ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded shadow-sm text-center"><small class="text-muted d-block">Volume</small><span class="fw-bold text-primary"><?= number_format($totals['volume'], 2) ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded shadow-sm text-center"><small class="text-muted d-block">Deposits</small><span class="fw-bold text-success">$<?= number_format($totals['deposits'], 2) ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded shadow-sm text-center"><small class="text-muted d-block">Withdrawals</small><span class="fw-bold text-danger">$<?= number_format($totals['withdrawals'], 2) ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded shadow-sm text-center"><small class="text-muted d-block">Commission</small><span class="fw-bold text-warning">$<?= number_format($totals['commission'], 2) ?></span></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Data Table -->
    <!-- Data Table -->
    <div class="table-responsive border-0 shadow-sm rounded-4 text-nowrap">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <!-- Sembunyikan kolom di HP dengan d-none d-md-table-cell -->
                    <th class="px-3 py-2 border-bottom-0 d-none d-md-table-cell">Account ID</th>
                    <th class="py-2 border-bottom-0 px-3 px-md-2">Full Name</th>
                    <th class="py-2 border-bottom-0 text-center d-none d-md-table-cell">Platform</th>
                    <th class="py-2 border-bottom-0 text-center px-3 px-md-2">Status</th>
                    <th class="py-2 text-end border-bottom-0 d-none d-md-table-cell">Deposits</th>
                    <th class="py-2 text-end border-bottom-0 d-none d-md-table-cell">Volume</th>
                    <th class="py-2 text-center border-bottom-0 d-none d-md-table-cell px-3">Reg. Date</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Belum ada data klien untuk filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clients as $index => $client): ?> <!-- Tambahkan variabel $index untuk ID Collapse -->
                        <!-- Baris Utama -->
                        <tr>
                            <!-- Account ID (Hanya PC) -->
                            <td class="px-3 d-none d-md-table-cell">
                                <span class="badge bg-light text-primary border border-primary-subtle fs-7 px-2 py-1">
                                    <?= esc($client['account_id']) ?>
                                </span>
                            </td>

                            <!-- Full Name (PC & HP) -->
                            <td class="fw-bold py-2 px-3 px-md-2" style="font-size: 0.9rem;">
                                <!-- Tombol Collapse Khusus HP -->
                                <button class="btn btn-sm btn-light border rounded-circle d-md-none shadow-sm fw-bold p-1 d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; line-height: 1;" type="button" data-bs-toggle="collapse" data-bs-target="#expandClient<?= $index ?>" aria-expanded="false">
                                    <i class="bi bi-chevron-down text-primary" style="font-size: 0.75rem;"></i>
                                </button>

                                <?= esc($client['full_name']) ?>
                                <span class="d-none d-md-block text-muted fw-normal mt-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-geo-alt me-1"></i><?= esc($client['country']) ?>
                                </span>
                            </td>

                            <!-- Platform (Hanya PC) -->
                            <td class="text-center fw-semibold text-secondary small d-none d-md-table-cell">
                                <?= esc($client['platform']) ?>
                            </td>

                            <!-- Status (PC & HP) -->
                            <td class="text-center px-3 px-md-2">
                                <?php
                                $statusClass = 'bg-secondary';
                                if (strtolower($client['activity_status']) === 'active') $statusClass = 'bg-success';
                                elseif (stripos($client['activity_status'], 'archive') !== false) $statusClass = 'bg-dark';
                                ?>
                                <span class="badge <?= $statusClass ?> rounded-pill px-2" style="font-size: 0.7rem;">
                                    <?= esc($client['activity_status'] ?: 'Unknown') ?>
                                </span>
                            </td>

                            <!-- Deposits (Hanya PC) -->
                            <td class="text-end text-success fw-semibold d-none d-md-table-cell" style="font-size: 0.9rem;">
                                $<?= number_format($client['deposits'], 2) ?>
                            </td>

                            <!-- Volume (Hanya PC) -->
                            <td class="text-end text-primary fw-semibold d-none d-md-table-cell" style="font-size: 0.9rem;">
                                <?= number_format($client['volume'], 2) ?>
                            </td>

                            <!-- Reg Date (Hanya PC) -->
                            <td class="text-center small text-muted px-3 d-none d-md-table-cell">
                                <?= date('d M Y', strtotime($client['account_regdate'])) ?>
                            </td>
                        </tr>

                        <!-- Baris Expand / Collapse (Hanya muncul di HP jika tombol ditekan) -->
                        <tr id="expandClient<?= $index ?>" class="collapse d-md-none bg-light border-bottom">
                            <td colspan="2" class="px-4 py-3 shadow-inner"> <!-- colspan="2" karena hanya ada 2 kolom di HP -->
                                <div class="row g-3 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Account ID</span>
                                        <span class="badge bg-white text-primary border border-primary-subtle px-2 py-1">
                                            <?= esc($client['account_id']) ?>
                                        </span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Platform</span>
                                        <span class="fw-semibold text-dark"><?= esc($client['platform']) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Deposits</span>
                                        <span class="fw-bold text-success">$<?= number_format($client['deposits'], 2) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Volume</span>
                                        <span class="fw-bold text-primary"><?= number_format($client['volume'], 2) ?></span>
                                    </div>
                                    <div class="col-12 border-top pt-2 mt-2">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Negara</span>
                                                <span class="text-dark fw-semibold"><i class="bi bi-geo-alt me-1"></i><?= esc($client['country']) ?></span>
                                            </div>
                                            <div class="text-end">
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">Tgl. Registrasi</span>
                                                <span class="text-dark"><i class="bi bi-calendar-event me-1"></i><?= date('d M Y', strtotime($client['account_regdate'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>