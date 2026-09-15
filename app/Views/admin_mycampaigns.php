<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">
    <!-- Header -->
    <div class="row align-items-md-center justify-content-between mb-4 g-3">
        <div class="col-12 col-md">
            <h4 class="fw-bold tracking-tight text-primary mb-1">
                <i class="bi bi-megaphone-fill me-2"></i>Performa Kampanye (API)
            </h4>
            <p class="text-muted small mb-0">Data performa link IB dan statistik kampanye dari sistem HFM secara realtime.</p>
        </div>
        <div class="col-12 col-md-auto d-flex gap-2">
            <?php if (isset($campaigns) && count($campaigns) > 0): ?>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 d-flex align-items-center fw-semibold">
                    Total: <?= count($campaigns) ?> Kampanye
                </span>
            <?php endif; ?>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-1"></i> Segarkan Data
            </button>
        </div>
    </div>

    <!-- Peringatan Jika Error Koneksi -->
    <?php if (isset($debug_api) && $debug_api): ?>
        <div class="alert alert-warning shadow-sm rounded-3">
            <strong><i class="bi bi-exclamation-triangle me-1"></i> Terjadi Kesalahan:</strong> <?= esc($debug_api) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($http_code) && $http_code !== 200 && $http_code !== 0): ?>
        <div class="alert alert-danger shadow-sm rounded-3">
            <strong>HTTP Status:</strong> <?= esc($http_code) ?> - Gagal menghubungi HFM.
        </div>
    <?php endif; ?>

    <!-- Grid Data Kampanye -->
    <div class="row g-4">
        <?php if (!empty($campaigns) && is_array($campaigns)): ?>
            <?php foreach ($campaigns as $camp): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card data-card p-4 h-100 border-top border-primary border-4 shadow-sm rounded-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0 text-truncate" style="max-width: 75%;" title="<?= esc($camp['name'] ?? 'Unnamed') ?>">
                                <?= esc($camp['name'] ?? 'Unnamed Campaign') ?>
                            </h6>
                            <span class="badge bg-light text-dark border small rounded-pill"><?= esc($camp['type'] ?? 'Standard') ?></span>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <p class="text-muted small mb-0" style="font-size: 0.75rem;"><i class="bi bi-cursor text-secondary me-1"></i> Total Klik</p>
                                <h5 class="fw-bold mb-0"><?= number_format($camp['clicks'] ?? 0) ?></h5>
                            </div>
                            <div class="col-6">
                                <p class="text-muted small mb-0" style="font-size: 0.75rem;"><i class="bi bi-people text-secondary me-1"></i> Total Akun</p>
                                <h5 class="fw-bold mb-0"><?= number_format($camp['total_trading_account_registrations'] ?? 0) ?></h5>
                            </div>
                            <div class="col-6">
                                <p class="text-muted small mb-0" style="font-size: 0.75rem;"><i class="bi bi-person-check text-success me-1"></i> Akun Aktif</p>
                                <h5 class="fw-bold text-success mb-0"><?= number_format($camp['active_trading_account_registrations'] ?? 0) ?></h5>
                            </div>
                            <div class="col-6">
                                <p class="text-muted small mb-0" style="font-size: 0.75rem;"><i class="bi bi-cash-coin text-warning me-1"></i> Komisi</p>
                                <h5 class="fw-bold text-dark mb-0">$<?= number_format($camp['commission'] ?? 0, 2) ?></h5>
                            </div>
                        </div>

                        <!-- Link Referal -->
                        <?php if (!empty($camp['main_link'])): ?>
                            <div class="mt-4 pt-3 border-top">
                                <p class="small text-muted mb-2 fw-semibold" style="font-size: 0.8rem;">Tautan Afiliasi (Referral):</p>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control bg-light text-muted border-secondary-subtle" value="<?= esc($camp['main_link']) ?>" readonly id="link-<?= md5($camp['name'] ?? rand()) ?>">
                                    <button class="btn btn-outline-primary fw-semibold px-3" type="button" onclick="salinLink('link-<?= md5($camp['name'] ?? rand()) ?>')">
                                        <i class="bi bi-clipboard"></i> Salin
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <!-- Tampilan Kosong (Empty State) -->
            <div class="col-12">
                <div class="card p-5 text-center border-dashed bg-light rounded-4 shadow-sm" style="border: 2px dashed #dee2e6;">
                    <i class="bi bi-megaphone text-muted opacity-50 mb-3" style="font-size: 4rem;"></i>
                    <h5 class="fw-bold text-secondary">Belum Ada Data Kampanye</h5>
                    <p class="text-muted small mb-0">API mengembalikan data kosong (0 kampanye). Buat kampanye baru di Dashboard IB HFM Anda agar statistik muncul di sini.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function salinLink(inputId) {
        var copyText = document.getElementById(inputId);
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value).then(() => {
            alert("Tautan kampanye berhasil disalin!");
        }).catch(err => {
            console.error('Gagal menyalin:', err);
        });
    }
</script>
<?= $this->endSection() ?>