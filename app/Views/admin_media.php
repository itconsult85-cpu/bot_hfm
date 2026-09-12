<?= $this->extend('layout/template') ?>

<?= $this->section('styles') ?>
<style>
    .data-card {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        border-radius: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .data-card:hover {
        transform: translateX(4px);
        box-shadow: 0 12px 20px rgba(0, 0, 0, 0.03);
        border-color: #cbd5e1;
    }

    .media-preview {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        transition: transform 0.2s;
    }

    .media-preview:hover {
        transform: scale(2.5);
        z-index: 10;
        position: relative;
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.2);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-4 mt-2">
    <div>
        <h3 class="fw-bold mb-1">Kelola Panduan Bot</h3>
        <p class="text-muted small">Atur keyword dan media pendukung untuk automasi chat.</p>
    </div>
    <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="bi bi-plus-lg me-1"></i> Tambah Panduan
    </button>
</div>

<div class="row g-3">
    <?php foreach ($media as $m): ?>
        <div class="col-12">
            <div class="card data-card p-3">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <span class="badge bg-primary-subtle text-primary border rounded-pill px-3"><?= $m['keyword'] ?></span>
                    </div>
                    <div class="col-md-5">
                        <small class="text-muted d-block">Teks Bot</small>
                        <span class="fw-semibold" style="white-space: pre-line;"><?= esc($m['teks_panduan']) ?></span>
                    </div>
                    <div class="col-md-1">
                        <small class="text-muted d-block">Gambar</small>
                        <?php if ($m['url_gambar']): ?>
                            <img src="<?= $m['url_gambar'] ?>" class="media-preview shadow-sm" alt="img">
                        <?php else: ?> - <?php endif; ?>
                    </div>
                    <div class="col-md-1">
                        <small class="text-muted d-block">Video</small>
                        <?php if ($m['url_video']): ?>
                            <a href="<?= $m['url_video'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill"><i class="bi bi-play-fill"></i> Link</a>
                        <?php else: ?> - <?php endif; ?>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <button class="btn btn-light btn-sm rounded-circle p-2 border me-2" onclick="bukaModalEdit(this)" data-id="<?= $m['id'] ?>" data-keyword="<?= $m['keyword'] ?>" data-teks="<?= htmlspecialchars($m['teks_panduan']) ?>"><i class="bi bi-pencil text-warning"></i></button>
                        <button class="btn btn-light btn-sm rounded-circle p-2 border" onclick="konfirmasiHapus('<?= $m['id'] ?>', '<?= $m['keyword'] ?>')"><i class="bi bi-trash3 text-danger"></i></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <form action="<?= base_url('MediaAdmin/upload') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Tambah Panduan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Keyword</label>
                        <input type="text" name="keyword" class="form-control" placeholder="Contoh: ktp" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Teks Balasan Bot</label>
                        <textarea name="teks_panduan" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-semibold text-muted">Gambar</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-semibold text-muted">Video (MP4)</label>
                            <input type="file" name="video" class="form-control" accept="video/mp4">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <form action="<?= base_url('MediaAdmin/update') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Edit Panduan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Keyword</label>
                        <input type="text" name="keyword" id="edit_keyword" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Teks Balasan</label>
                        <textarea name="teks_panduan" id="edit_teks" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-semibold text-muted">Ganti Gambar</label>
                            <input type="file" name="gambar" class="form-control" accept="image/*">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-semibold text-muted">Ganti Video</label>
                            <input type="file" name="video" class="form-control" accept="video/mp4">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 text-white">Update Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHapus" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow border-0 rounded-4">
            <div class="modal-body text-center p-4">
                <i class="bi bi-trash3 text-danger fs-1"></i>
                <h6 class="fw-bold mt-2">Hapus Panduan?</h6>
                <p class="text-muted small">Yakin ingin menghapus keyword: <b id="teksKeyword"></b>?</p>
                <form id="formHapus" action="<?= base_url('MediaAdmin/hapus') ?>" method="POST">
                    <input type="hidden" name="id" id="inputHapusId">
                    <div class="d-grid gap-2 mt-3">
                        <button type="submit" class="btn btn-danger rounded-pill fw-semibold">Ya, Hapus</button>
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function bukaModalEdit(btn) {
        document.getElementById('edit_id').value = btn.getAttribute('data-id');
        document.getElementById('edit_keyword').value = btn.getAttribute('data-keyword');
        document.getElementById('edit_teks').value = btn.getAttribute('data-teks');
        new bootstrap.Modal(document.getElementById('modalEdit')).show();
    }

    function konfirmasiHapus(id, keyword) {
        document.getElementById('teksKeyword').innerText = keyword;
        document.getElementById('inputHapusId').value = id;
        new bootstrap.Modal(document.getElementById('modalHapus')).show();
    }
</script>
<?= $this->endSection() ?>