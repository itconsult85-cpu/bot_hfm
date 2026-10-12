<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<?php
$stageLabels = [
    1 => 'Status akun',
    2 => 'Pendaftaran',
    3 => 'Screenshot akun',
    4 => 'Pindah IB',
    5 => 'Arsip akun',
    6 => 'Buka MT5 / deposit',
    7 => 'Formulir',
];
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 mt-2">
    <div>
        <h3 class="fw-bold mb-1"><i class="bi bi-person-lines-fill text-primary me-2"></i>Permintaan Bantuan Admin</h3>
        <p class="text-muted small mb-0">Riwayat chat dan identitas Telegram user untuk dihubungi langsung oleh admin.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= base_url('admin/help-sessions') ?>" class="btn btn-primary rounded-pill px-3 fw-semibold">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh daftar
        </a>
        <a href="<?= base_url('admin/member-logs') ?>" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke log member
        </a>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-warning rounded-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc($errorMessage) ?>
    </div>
<?php else: ?>
    <div class="alert alert-info rounded-4 small" role="status">
        <i class="bi bi-info-circle-fill me-2"></i>
        Ringkasan kendala dikirim satu kali saat user meminta bantuan, berisi tahap proses, keterangan terakhir, dan foto user jika ada—bukan salinan seluruh riwayat chat. Pesan berikutnya tidak diteruskan otomatis dan balasan admin tidak melalui bot. Klik ID Telegram atau tombol profil untuk menghubungi user langsung.
        Daftar dibaca dari database sehingga tetap tersedia meskipun proses bot di-restart.
    </div>

    <div class="d-flex align-items-center gap-2 mb-3">
        <span class="badge text-bg-primary rounded-pill px-3 py-2"><?= number_format(count($requests)) ?> permintaan</span>
        <small class="text-muted">Diperbarui: <?= esc($refreshedAt ?? '') ?></small>
    </div>

    <div class="table-responsive bg-white rounded-4 shadow-sm border border-light">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama user</th>
                    <th>ID Telegram</th>
                    <th>Tahap</th>
                    <th>Waktu permintaan</th>
                    <th class="text-end">Kontak langsung</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-chat-square-text fs-2 d-block mb-2"></i>
                            Belum ada permintaan bantuan admin.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <?php
                        $userId = (string) ($request['userId'] ?? '');
                        $requestedAtRaw = $request['requestedAt'] ?? '';
                        $requestedAt = is_numeric($requestedAtRaw)
                            ? (int) floor(((int) $requestedAtRaw) / 1000)
                            : (strtotime((string) $requestedAtRaw) ?: 0);
                        $step = (int) ($request['currentStep'] ?? 0);
                        ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= esc(($request['userName'] ?? '') !== '' ? $request['userName'] : 'Pengguna Telegram') ?></div>
                                <?php if (!empty($request['username'])): ?>
                                    <small class="text-muted">@<?= esc($request['username']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <?php if (ctype_digit($userId)): ?>
                                    <a href="tg://user?id=<?= esc($userId, 'attr') ?>" title="Buka profil Telegram user"><code><?= esc($userId) ?></code></a>
                                <?php else: ?>
                                    <code><?= esc($userId) ?></code>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge text-bg-light border text-dark"><?= esc($stageLabels[$step] ?? ('Tahap ' . $step)) ?></span></td>
                            <td class="text-nowrap small"><?= $requestedAt > 0 ? esc(date('d M Y H:i:s', $requestedAt)) : '-' ?></td>
                            <td class="text-end text-nowrap">
                                <?php if (ctype_digit($userId)): ?>
                                    <a href="tg://user?id=<?= esc($userId, 'attr') ?>" class="btn btn-sm btn-outline-success" title="Buka profil Telegram user">
                                        <i class="bi bi-telegram me-1"></i>Buka profil
                                    </a>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-outline-primary copy-user-id" data-user-id="<?= esc($userId, 'attr') ?>">
                                    <i class="bi bi-clipboard me-1"></i>Salin ID
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.querySelectorAll('.copy-user-id').forEach(button => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.userId || '');
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="bi bi-check2 me-1"></i>Tersalin';
                setTimeout(() => button.innerHTML = originalText, 1500);
            } catch (error) {
                window.prompt('Salin ID Telegram ini:', button.dataset.userId || '');
            }
        });
    });
</script>
<?= $this->endSection() ?>
