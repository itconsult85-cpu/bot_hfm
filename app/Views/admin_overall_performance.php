<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold tracking-tight text-primary mb-1">
                <i class="bi bi-bar-chart-line me-2"></i><?= esc($title) ?>
            </h4>
            <p class="text-muted small mb-0">Rangkuman kinerja afiliasi secara keseluruhan.</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-light p-3 rounded-4 border mb-4">
        <form method="GET" action="<?= base_url('AdminDashboard/overallPerformance') ?>" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted fw-semibold">Dari Tanggal</label>
                <input type="date" class="form-control form-control-sm rounded-3" name="from_date" value="<?= esc($date_from) ?>" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small text-muted fw-semibold">Sampai Tanggal</label>
                <input type="date" class="form-control form-control-sm rounded-3" name="to_date" value="<?= esc($date_to) ?>" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted fw-semibold">Kelompokkan Berdasarkan (Group By)</label>
                <select class="form-select form-select-sm rounded-3" name="group_by">
                    <option value="day" <?= $group_by == 'day' ? 'selected' : '' ?>>Harian (Day)</option>
                    <option value="week" <?= $group_by == 'week' ? 'selected' : '' ?>>Mingguan (Week)</option>
                    <option value="month" <?= $group_by == 'month' ? 'selected' : '' ?>>Bulanan (Month)</option>
                    <option value="year" <?= $group_by == 'year' ? 'selected' : '' ?>>Tahunan (Year)</option>
                    <option value="country" <?= $group_by == 'country' ? 'selected' : '' ?>>Negara (Country)</option>
                    <option value="campaign" <?= $group_by == 'campaign' ? 'selected' : '' ?>>Kampanye (Campaign)</option>
                    <option value="account_type" <?= $group_by == 'account_type' ? 'selected' : '' ?>>Tipe Akun (Account Type)</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold rounded-pill py-2 shadow-sm">
                    <i class="bi bi-filter"></i> Terapkan
                </button>
            </div>
        </form>
    </div>

    <!-- Notifikasi Error API -->
    <?php if ($debug_api): ?>
        <div class="alert alert-warning small py-2 rounded-3 shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i><?= esc($debug_api) ?></div>
    <?php endif; ?>

    <!-- Summary (Totals) Cards -->
    <?php if (!empty($totals)): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded-4 shadow-sm text-center"><small class="text-muted d-block fw-semibold">Client Reg.</small><span class="fw-bold fs-5"><?= number_format($totals['client_registrations'] ?? 0) ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded-4 shadow-sm text-center"><small class="text-muted d-block fw-semibold">Accounts</small><span class="fw-bold fs-5"><?= number_format($totals['accounts'] ?? 0) ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded-4 shadow-sm text-center"><small class="text-muted d-block fw-semibold">Volume</small><span class="fw-bold text-primary fs-5"><?= number_format($totals['volume'] ?? 0, 2) ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded-4 shadow-sm text-center"><small class="text-muted d-block fw-semibold">Deposits</small><span class="fw-bold text-success fs-5">$<?= number_format($totals['deposits'] ?? 0, 2) ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded-4 shadow-sm text-center"><small class="text-muted d-block fw-semibold">Withdrawals</small><span class="fw-bold text-danger fs-5">$<?= number_format($totals['withdrawals'] ?? 0, 2) ?></span></div>
            </div>
            <div class="col-6 col-md-2">
                <div class="p-3 bg-white border rounded-4 shadow-sm text-center"><small class="text-muted d-block fw-semibold">Commission</small><span class="fw-bold text-warning fs-5">$<?= number_format($totals['commission'] ?? 0, 2) ?></span></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Data Table -->
    <!-- Data Table -->
    <div class="table-responsive border-0 overflow-hidden shadow-sm rounded-4">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="px-3 py-2 border-bottom-0">
                        <?= ucfirst(str_replace('_', ' ', esc($group_by))) ?>
                    </th>
                    <!-- Class d-none d-md-table-cell akan menyembunyikan kolom ini di HP -->
                    <th class="py-2 border-bottom-0 text-center d-none d-md-table-cell">Clients</th>
                    <th class="py-2 border-bottom-0 text-center d-none d-md-table-cell">Accounts</th>
                    <th class="py-2 text-end border-bottom-0 d-none d-md-table-cell">Deposits</th>
                    <th class="py-2 text-end border-bottom-0 d-none d-md-table-cell">Withdrawals</th>
                    <th class="py-2 text-end border-bottom-0 d-none d-md-table-cell">Volume</th>
                    <th class="py-2 text-end border-bottom-0 px-3">Commission</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                <?php if (empty($performance_data)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">Belum ada data performa untuk rentang waktu ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($performance_data as $index => $row): ?> <!-- Tambahkan $index untuk ID unik -->
                        <!-- Baris Utama -->
                        <tr>
                            <td class="px-3 py-2 fw-bold text-dark text-nowrap">
                                <!-- Tombol Expand Khusus HP -->
                                <button class="btn btn-sm btn-light border rounded-circle d-md-none shadow-sm fw-bold p-1 d-inline-flex align-items-center justify-content-center me-2" style="width: 24px; height: 24px; line-height: 1;" type="button" data-bs-toggle="collapse" data-bs-target="#expandPerf<?= $index ?>" aria-expanded="false">
                                    <i class="bi bi-chevron-down text-primary" style="font-size: 0.75rem;"></i>
                                </button>
                                <?= esc($row['group_by_value'] ?: 'Unknown') ?>
                            </td>

                            <!-- Kolom-kolom yang disembunyikan di HP -->
                            <td class="text-center fw-semibold text-secondary small d-none d-md-table-cell">
                                <?= number_format($row['client_registrations'] ?? 0) ?>
                            </td>
                            <td class="text-center fw-semibold text-secondary small d-none d-md-table-cell">
                                <?= number_format($row['account_registrations'] ?? 0) ?>
                            </td>
                            <td class="text-end text-success fw-semibold d-none d-md-table-cell" style="font-size: 0.9rem;">
                                $<?= number_format($row['deposits'] ?? 0, 2) ?>
                            </td>
                            <td class="text-end text-danger fw-semibold d-none d-md-table-cell" style="font-size: 0.9rem;">
                                $<?= number_format($row['withdrawals'] ?? 0, 2) ?>
                            </td>
                            <td class="text-end text-primary fw-semibold d-none d-md-table-cell" style="font-size: 0.9rem;">
                                <?= number_format($row['volume'] ?? 0, 2) ?>
                            </td>

                            <!-- Kolom Komisi tetap tampil di HP dan PC -->
                            <td class="text-end text-warning fw-semibold px-3 text-nowrap" style="font-size: 0.9rem;">
                                $<?= number_format($row['commission'] ?? 0, 2) ?>
                            </td>
                        </tr>

                        <!-- Baris Expand / Collapse (Hanya muncul di HP jika tombol ditekan) -->
                        <tr id="expandPerf<?= $index ?>" class="collapse d-md-none bg-light border-bottom">
                            <!-- colspan="2" karena di HP hanya ada 2 kolom utama yang terlihat -->
                            <td colspan="2" class="px-4 py-3 shadow-inner">
                                <div class="row g-3 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Clients / Accounts</span>
                                        <span class="fw-bold text-dark"><?= number_format($row['client_registrations'] ?? 0) ?> / <?= number_format($row['account_registrations'] ?? 0) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Volume</span>
                                        <span class="fw-bold text-primary"><?= number_format($row['volume'] ?? 0, 2) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Deposits</span>
                                        <span class="fw-bold text-success">$<?= number_format($row['deposits'] ?? 0, 2) ?></span>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block" style="font-size: 0.75rem;">Withdrawals</span>
                                        <span class="fw-bold text-danger">$<?= number_format($row['withdrawals'] ?? 0, 2) ?></span>
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