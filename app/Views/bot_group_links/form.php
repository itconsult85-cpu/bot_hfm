<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>
<div class="card border-0 shadow-sm rounded-4 p-4" style="max-width: 760px;">
    <h4 class="fw-bold mb-1"><i class="bi bi-link-45deg text-primary me-2"></i><?= esc($title) ?></h4>
    <p class="text-muted small mb-4">Simpan link undangan dan ID grup yang digunakan bot untuk pengiriman dan kick.</p>
    <?php if ($message = session()->getFlashdata('error')): ?><div class="alert alert-danger"><?= esc($message) ?></div><?php endif; ?>
    <form method="post" action="<?= esc($action) ?>">
        <?= csrf_field() ?>
        <div class="mb-3"><label class="form-label fw-semibold">Platform</label><select class="form-select" name="platform" required><option value="telegram" <?= old('platform', $link['platform'] ?? '') === 'telegram' ? 'selected' : '' ?>>Telegram</option><option value="whatsapp" <?= old('platform', $link['platform'] ?? '') === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option></select></div>
        <div class="mb-3"><label class="form-label fw-semibold">Nama grup</label><input class="form-control" name="group_name" maxlength="150" required value="<?= esc(old('group_name', $link['group_name'] ?? '')) ?>" placeholder="Contoh: Grup VIP WhatsApp"></div>
        <div class="mb-3"><label class="form-label fw-semibold">Link undangan HTTPS</label><input class="form-control" type="url" name="invite_link" maxlength="500" required value="<?= esc(old('invite_link', $link['invite_link'] ?? '')) ?>" placeholder="https://chat.whatsapp.com/... atau https://t.me/..."></div>
        <div class="mb-3"><label class="form-label fw-semibold">ID grup</label><input class="form-control" name="group_id" maxlength="100" required value="<?= esc(old('group_id', $link['group_id'] ?? '')) ?>" placeholder="Telegram: -100... | WhatsApp: 123456789@g.us"><div class="form-text">ID ini wajib benar agar bot dapat memantau join dan menjalankan kick.</div></div>
        <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" <?= old('is_active', (string) ($link['is_active'] ?? '1')) ? 'checked' : '' ?>><label class="form-check-label" for="is_active">Aktif digunakan bot</label></div>
        <a href="<?= base_url('bot-group-links') ?>" class="btn btn-light rounded-pill px-4">Batal</a> <button class="btn btn-primary rounded-pill px-4" type="submit"><i class="bi bi-save me-1"></i>Simpan</button>
    </form>
</div>
<?= $this->endSection() ?>
