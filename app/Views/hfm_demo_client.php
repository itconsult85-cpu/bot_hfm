<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-beaker text-primary me-2"></i>HFM Demo Client</h4>
        <p class="text-muted small mb-0">Memeriksa apakah sebuah client wallet memiliki data demo di API HFM.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold mb-1">Uji endpoint</h5>
            <p class="text-muted small mb-4">Request ini hanya membaca data dari HFM dan tidak mengubah wallet.</p>
            <form method="post" action="<?= base_url('hfm-demo-client/check') ?>">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label for="client_wallet_id" class="form-label fw-semibold">Client wallet ID <span
                            class="text-danger">*</span></label>
                    <input type="text" name="client_wallet_id" id="client_wallet_id" class="form-control"
                        inputmode="numeric" pattern="[1-9][0-9]*" maxlength="30" required value="<?= esc($walletId) ?>"
                        placeholder="Contoh: 135152963">
                    <div class="form-text">Satu-satunya parameter yang dikirim ke
                        <code>/api/clients/{client_wallet_id}/demo</code>.
                    </div>
                </div>
                <button type="submit" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-arrow-repeat me-1"></i> Periksa Demo Client
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold mb-3">Apa fungsi endpoint ini?</h5>
            <p class="mb-3">Endpoint ini digunakan untuk membaca informasi client demo yang berhubungan dengan sebuah
                <strong>client wallet ID</strong>. Dari response yang Anda berikan, API menyediakan status arsip dan ID
                relasi demo.
            </p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Arti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>id</code></td>
                            <td>ID client demo pada sistem HFM.</td>
                        </tr>
                        <tr>
                            <td><code>server_id</code></td>
                            <td>ID server tempat client demo terdaftar.</td>
                        </tr>
                        <tr>
                            <td><code>wallet_id</code></td>
                            <td>ID wallet yang terkait dengan client demo.</td>
                        </tr>
                        <tr>
                            <td><code>is_archived</code></td>
                            <td><code>true</code> berarti data demo tersebut berstatus diarsipkan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="alert alert-info small mt-4 mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Jika API mengembalikan nilai ID <code>0</code>, jangan langsung dianggap sebagai wallet baru. Nilai
                tersebut perlu dikonfirmasi dengan dokumentasi HFM atau hasil endpoint lain karena bisa berarti tidak
                ada data demo yang ditemukan.
            </div>
        </div>
    </div>
</div>

<?php if ($result !== null): ?>
<div class="card border-0 shadow-sm rounded-4 p-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0">Hasil pemeriksaan</h5>
        <?php if (isset($result['http_code'])): ?>
        <span class="badge <?= $result['ok'] ? 'text-bg-success' : 'text-bg-danger' ?>">HTTP
            <?= esc($result['http_code']) ?></span>
        <?php endif; ?>
    </div>
    <div class="alert <?= $result['ok'] ? 'alert-success' : 'alert-danger' ?> small">
        <?= esc($result['message'] ?? 'Tidak ada keterangan.') ?>
    </div>
    <?php if (isset($result['response'])): ?>
    <?php
            $demoRows = is_array($result['response']['data'] ?? null) ? $result['response']['data'] : [];
            $archivedCount = count(array_filter($demoRows, static fn($row) => !empty($row['is_archived'])));
            $activeCount = count($demoRows) - $archivedCount;
            $verificationClass = $activeCount > 0 ? 'alert-success' : ($archivedCount > 0 ? 'alert-warning' : 'alert-danger');
            $verificationIcon = $activeCount > 0 ? 'bi-check-circle' : ($archivedCount > 0 ? 'bi-archive' : 'bi-x-circle');
            $verificationTitle = $activeCount > 0
                ? 'Terverifikasi terdaftar di IB yang diperiksa'
                : ($archivedCount > 0 ? 'Pernah terdaftar, tetapi seluruh record sudah diarsipkan' : 'Tidak ditemukan di IB yang diperiksa');
            $verificationDescription = $activeCount > 0
                ? 'Wallet memiliki setidaknya satu record demo aktif pada response API HFM.'
                : ($archivedCount > 0 ? 'Wallet memiliki record pada IB ini, tetapi tidak ada record demo yang masih aktif.' : 'API berhasil merespons, tetapi field data kosong atau null.');
            ?>
    <div class="alert <?= $verificationClass ?> d-flex align-items-start gap-2 mb-4">
        <i class="bi <?= $verificationIcon ?> fs-5"></i>
        <div>
            <strong><?= esc($verificationTitle) ?></strong>
            <div class="small mt-1"><?= esc($verificationDescription) ?></div>
        </div>
    </div>
    <?php if ($demoRows !== []): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stats-card p-3 data-card">
                <p class="text-muted small mb-1"><i class="bi bi-collection me-1"></i>Total Demo</p>
                <h3 class="stat-number text-primary"><?= number_format(count($demoRows)) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card p-3 data-card">
                <p class="text-muted small mb-1"><i class="bi bi-check-circle text-success me-1"></i>Aktif</p>
                <h3 class="stat-number text-success"><?= number_format($activeCount) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card p-3 data-card">
                <p class="text-muted small mb-1"><i class="bi bi-archive text-secondary me-1"></i>Diarsipkan</p>
                <h3 class="stat-number text-secondary"><?= number_format($archivedCount) ?></h3>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Demo Client ID</th>
                    <th>Wallet ID</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demoRows as $index => $demo): ?>
                <?php $isArchived = !empty($demo['is_archived']); ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td class="fw-semibold"><?= esc($demo['id'] ?? '-') ?></td>
                    <td><?= esc($demo['wallet_id'] ?? '-') ?></td>
                    <td>
                        <span class="badge <?= $isArchived ? 'text-bg-secondary' : 'text-bg-success' ?>">
                            <i class="bi <?= $isArchived ? 'bi-archive' : 'bi-check-circle' ?> me-1"></i>
                            <?= $isArchived ? 'Diarsipkan' : 'Aktif' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="alert alert-warning mb-0">Response berhasil, tetapi API tidak mengembalikan data demo.</div>
    <?php endif; ?>

    <?php elseif (isset($result['raw'])): ?>
    <pre class="bg-dark text-light rounded-3 p-3 mb-0"
        style="max-height: 360px; overflow:auto;"><?= esc($result['raw']) ?></pre>
    <?php endif; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
