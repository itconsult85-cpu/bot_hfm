<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">

    <div class="row align-items-md-center justify-content-between mb-3 g-3">
        <div class="col-12 col-md">
            <h4 class="fw-bold tracking-tight text-primary mb-1" style="font-size: 1.25rem;">
                <i class="bi bi-diagram-3 me-2"></i><?= esc($title ?? 'Alur Pendaftaran (Flows)') ?>
            </h4>
            <p class="text-muted small mb-0" style="font-size: 0.8rem;">Atur langkah-langkah pendaftaran user bot Telegram secara berjenjang.</p>
        </div>
        <div class="col-12 col-md-auto">
            <a href="<?= base_url('bot-flow/create') ?>" class="btn btn-primary rounded-pill w-100 px-4 py-2 small fw-semibold shadow-sm text-nowrap">
                <i class="bi bi-plus-lg me-1"></i> Tambah Alur
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('pesan')): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 py-2 mb-3 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('pesan') ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="table-responsive border-0 overflow-hidden">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center border-bottom-0 px-1 px-md-3" style="width: 45px;">No</th>
                    <th class="text-center border-bottom-0 px-1 px-md-2" style="width: 60px;">Step</th>
                    <th class="border-bottom-0 px-1 px-md-3">Nama Step</th>
                    <th class="border-bottom-0 d-none d-md-table-cell" style="width: 25%;">Keywords Trigger</th>
                    <th class="border-bottom-0 d-none d-md-table-cell" style="width: 35%;">Pesan Balasan Utama</th>
                    <th class="text-center border-bottom-0 px-1 px-md-3" style="width: 90px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($flows)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-diagram-2 text-muted" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3">Belum Ada Alur Pendaftaran</h6>
                                <p class="text-muted small">Tambahkan step pertama untuk memulai.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1;
                    foreach ($flows as $f): ?>
                        <tr>
                            <td class="text-center px-1 px-md-3">
                                <button class="btn btn-sm btn-light border rounded-circle d-md-none shadow-sm fw-bold p-1 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; line-height: 1;" type="button" data-bs-toggle="collapse" data-bs-target="#expandFlow<?= $f['id'] ?>" aria-expanded="false">
                                    <i class="bi bi-chevron-down text-primary" style="font-size: 0.75rem;"></i>
                                </button>
                                <span class="text-muted small fw-semibold d-none d-md-inline"><?= $no++ ?></span>
                            </td>

                            <td class="text-center px-1 px-md-2">
                                <span class="badge bg-primary rounded-pill px-2 py-1 fs-6 shadow-sm">
                                    <?= esc($f['step_level']) ?>
                                </span>
                            </td>

                            <td class="py-2 px-1 px-md-3">
                                <span class="fw-bold text-dark d-block" style="word-break: break-word; font-size: 0.9rem;">
                                    <?= esc($f['step_name']) ?>
                                </span>
                            </td>

                            <td class="d-none d-md-table-cell">
                                <span class="badge bg-light text-secondary border text-wrap text-start lh-base small">
                                    <?= esc($f['trigger_keywords']) ?>
                                </span>
                            </td>

                            <td class="d-none d-md-table-cell">
                                <div class="text-muted small pe-3" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= esc($f['reply_message']) ?>
                                </div>
                            </td>

                            <td class="text-center px-1 px-md-3">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= base_url('bot-flow/edit/' . $f['id']) ?>" class="btn btn-sm btn-light border p-1 p-md-2 rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Edit">
                                        <i class="bi bi-pencil text-warning" style="font-size: 0.8rem;"></i>
                                    </a>

                                    <button type="button" class="btn btn-sm btn-light border rounded-circle p-1 p-md-2 d-inline-flex align-items-center justify-content-center btn-hapus-flow" style="width: 28px; height: 28px;"
                                        data-id="<?= $f['id'] ?>"
                                        data-nama="<?= esc($f['step_name']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalHapusFlow" title="Hapus">
                                        <i class="bi bi-trash text-danger" style="font-size: 0.8rem;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr id="expandFlow<?= $f['id'] ?>" class="collapse d-md-none bg-light border-bottom">
                            <td colspan="4" class="p-3 shadow-inner">
                                <ul class="list-unstyled mb-0 small px-1" style="font-size: 0.8rem;">
                                    <li class="mb-3">
                                        <span class="fw-semibold d-block text-muted mb-1">Keywords Trigger:</span>
                                        <span class="badge bg-white text-secondary border text-wrap text-start lh-base">
                                            <?= esc($f['trigger_keywords']) ?>
                                        </span>
                                    </li>
                                    <li>
                                        <span class="fw-semibold d-block text-muted mb-1">Pesan Balasan Utama:</span>
                                        <div class="bg-white p-2 border rounded text-muted" style="white-space: pre-wrap; font-size: 0.75rem;">
                                            <?= esc($f['reply_message']) ?>
                                        </div>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalHapusFlow" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-body p-4 text-center">
                <div class="display-6 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h5 class="fw-bold mb-2">Hapus Step Alur?</h5>
                <p class="text-muted small">Apakah Anda yakin ingin menghapus step <br>
                    <strong class="text-dark" id="teksNamaHapusFlow"></strong> secara permanen?
                </p>
                <div class="d-grid gap-2 mt-4">
                    <a href="#" id="linkHapusFlowModal" class="btn btn-danger rounded-pill py-2 fw-semibold">
                        <i class="bi bi-trash3 me-2"></i>Ya, Hapus Step
                    </a>
                    <button type="button" class="btn btn-link text-muted btn-sm text-decoration-none" data-bs-dismiss="modal">Batalkan</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.btn-hapus-flow').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');

                document.getElementById('teksNamaHapusFlow').innerText = nama;

                const linkHapus = document.getElementById('linkHapusFlowModal');
                if (linkHapus) {
                    linkHapus.href = "<?= base_url('bot-flow/delete/') ?>" + id;
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>