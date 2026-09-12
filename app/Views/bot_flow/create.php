<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-3">
    <div class="mb-4">
        <h3 class="fw-bold tracking-tight mb-1">
            <i class="bi bi-diagram-3 text-primary me-2"></i><?= esc($title ?? 'Form Alur Pendaftaran') ?>
        </h3>
        <p class="text-muted small">Isi detail tahapan untuk memandu user bot Telegram secara berjenjang.</p>
    </div>

    <div class="card shadow-sm border-0 rounded-4 p-4 mx-auto" style="max-width: 800px;">
        <form action="<?= isset($flow) ? base_url('bot-flow/update/' . $flow['id']) : base_url('bot-flow/store') ?>" method="POST">

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small">Step Level (Angka)</label>
                    <input type="number" name="step_level" class="form-control rounded-3" value="<?= isset($flow) ? esc($flow['step_level']) : '' ?>" placeholder="Contoh: 1" required>
                </div>
                <div class="col-md-9">
                    <label class="form-label fw-semibold text-muted small">Nama Step</label>
                    <input type="text" name="step_name" class="form-control rounded-3" value="<?= isset($flow) ? esc($flow['step_name']) : '' ?>" placeholder="Contoh: Minta ID Trading" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-muted small">Keywords Trigger (Pisahkan dengan koma)</label>
                <textarea name="trigger_keywords" class="form-control rounded-3" rows="2" placeholder="Contoh: sudah, siap, lanjut, oke" required><?= isset($flow) ? esc($flow['trigger_keywords']) : '' ?></textarea>
                <div class="form-text" style="font-size: 0.75rem;">Kata kunci yang memicu user masuk ke step ini jika diketik.</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold text-muted small">Pesan Balasan Utama</label>
                <textarea name="reply_message" class="form-control rounded-3" rows="4" placeholder="Ketikkan balasan bot di sini..." required><?= isset($flow) ? esc($flow['reply_message']) : '' ?></textarea>
                <div class="form-text" style="font-size: 0.75rem;">Gunakan <code>{NAMA_USER}</code> untuk menyapa nama. Gunakan <code>{LINK_DAFTAR}</code> untuk menyisipkan link.</div>
            </div>

            <hr class="my-4 text-muted border-light">

            <div class="mb-3">
                <label class="form-label fw-semibold text-muted small">Pesan Fallback (Opsional)</label>
                <textarea name="fallback_message" class="form-control rounded-3" rows="2" placeholder="Pesan jika input user di step ini salah..."><?= isset($flow) ? esc($flow['fallback_message']) : '' ?></textarea>
                <div class="form-text" style="font-size: 0.75rem;">Balasan jika user mengetik hal yang tidak sesuai dengan instruksi pada step ini.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold text-muted small">URL Video Fallback (Opsional)</label>
                <input type="url" name="fallback_video_url" class="form-control rounded-3" value="<?= isset($flow) ? esc($flow['fallback_video_url']) : '' ?>" placeholder="https://contoh.com/video-panduan.mp4">
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?= base_url('bot-flow') ?>" class="btn btn-light border rounded-pill px-4 fw-semibold text-muted">Batal</a>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm">
                    <i class="bi bi-save me-1"></i> <?= isset($flow) ? 'Update Alur' : 'Simpan Alur' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>