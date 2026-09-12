<?= $this->extend('layout/template') ?>

<?= $this->section('content') ?>
<div class="container-fluid mt-4">
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
            <h4 class="card-title fw-bold text-primary">
                <i class="bi bi-plus-circle me-2"></i><?= esc($title ?? 'Tambah Data FAQ') ?>
            </h4>
            <p class="text-muted small">Tambahkan keyword baru dan balasan otomatis untuk bot Anda.</p>
        </div>

        <form action="<?= base_url('bot-faq/store') ?>" method="post">
            <div class="card-body px-4">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Keywords (pisahkan dengan koma)</label>
                    <input type="text" name="keywords" class="form-control rounded-3" placeholder="Contoh: cara daftar, daftar, registrasi" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Reply Message</label>
                    <textarea name="reply_message" class="form-control rounded-3" rows="5" placeholder="Masukkan balasan teks bot di sini..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-muted">Action Type</label>
                    <select name="action_type" class="form-select rounded-3">
                        <option value="reply_only">Reply Only</option>
                        <option value="send_video_pindah">Send Video Pindah IB</option>
                    </select>
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 pb-4 px-4">
                <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Data</button>
                <a href="<?= base_url('bot-faq') ?>" class="btn btn-light rounded-pill px-4 border">Kembali</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>