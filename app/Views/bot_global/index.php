<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2 mb-4">

    <div class="row align-items-md-center justify-content-between mb-3 g-3">
        <div class="col-12 col-md">
            <h4 class="fw-bold tracking-tight text-primary mb-1" style="font-size: 1.25rem;">
                <i class="bi bi-globe me-2"></i><?= esc($title ?? 'Variabel Global Bot') ?>
            </h4>
            <p class="text-muted small mb-0" style="font-size: 0.8rem;">Kelola kata kunci statis yang digunakan bot di berbagai alur.</p>
        </div>
        <div class="col-12 col-md-auto">
            <a href="<?= base_url('bot-global/create') ?>" class="btn btn-primary rounded-pill w-100 px-4 py-2 small fw-semibold shadow-sm text-nowrap">
                <i class="bi bi-plus-lg me-1"></i> Tambah Variabel
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
                    <th class="border-bottom-0 px-1 px-md-3" style="width: 25%;">Nama Key</th>
                    <th class="border-bottom-0 d-none d-md-table-cell" style="width: 55%;">Nilai (Value)</th>
                    <th class="text-center border-bottom-0 px-1 px-md-3" style="width: 90px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($globals)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-code-slash text-muted" style="font-size: 3rem;"></i>
                                <h6 class="fw-bold mt-3">Tidak Ada Variabel</h6>
                                <p class="text-muted small">Belum ada variabel global yang didaftarkan.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1;
                    foreach ($globals as $g): ?>
                        <tr>
                            <td class="text-center px-1 px-md-3">
                                <button class="btn btn-sm btn-light border rounded-circle d-md-none shadow-sm fw-bold p-1 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; line-height: 1;" type="button" data-bs-toggle="collapse" data-bs-target="#expandVar<?= $g['id'] ?>" aria-expanded="false">
                                    <i class="bi bi-chevron-down text-primary" style="font-size: 0.75rem;"></i>
                                </button>
                                <span class="text-muted small fw-semibold d-none d-md-inline"><?= $i++ ?></span>
                            </td>

                            <td class="py-2 px-1 px-md-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill fw-semibold" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                                    {<?= esc($g['key_name']) ?>}
                                </span>
                            </td>

                            <td class="d-none d-md-table-cell">
                                <div class="text-muted small pe-3" style="white-space: pre-wrap; max-height: 80px; overflow-y: auto;">
                                    <?= esc($g['key_value']) ?>
                                </div>
                            </td>

                            <td class="text-center px-1 px-md-3">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="<?= base_url('bot-global/edit/' . $g['id']) ?>" class="btn btn-sm btn-light border rounded-circle p-1 p-md-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" title="Edit">
                                        <i class="bi bi-pencil text-warning" style="font-size: 0.8rem;"></i>
                                    </a>

                                    <button type="button" class="btn btn-sm btn-light border rounded-circle p-1 p-md-2 d-inline-flex align-items-center justify-content-center btn-hapus-var" style="width: 28px; height: 28px;"
                                        data-id="<?= $g['id'] ?>"
                                        data-nama="<?= esc($g['key_name']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalHapusVar" title="Hapus">
                                        <i class="bi bi-trash text-danger" style="font-size: 0.8rem;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr id="expandVar<?= $g['id'] ?>" class="collapse d-md-none bg-light border-bottom">
                            <td colspan="4" class="p-3 shadow-inner">
                                <span class="fw-semibold d-block text-muted mb-1 small">Nilai Variabel:</span>
                                <div class="bg-white p-2 border rounded text-muted small" style="white-space: pre-wrap; font-size: 0.8rem; overflow-wrap: break-word;">
                                    <?= esc($g['key_value']) ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalHapusVar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-body p-4 text-center">
                <div class="display-6 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h5 class="fw-bold mb-2">Hapus Variabel?</h5>
                <p class="text-muted small">Apakah Anda yakin ingin menghapus variabel global <br>
                    <strong class="text-dark bg-light px-1 rounded">{<span id="teksNamaHapusVar"></span>}</strong> secara permanen?
                </p>
                <div class="d-grid gap-2 mt-4">
                    <a href="#" id="linkHapusVarModal" class="btn btn-danger rounded-pill py-2 fw-semibold">
                        <i class="bi bi-trash3 me-2"></i>Ya, Hapus Variabel
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
        document.querySelectorAll('.btn-hapus-var').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');

                document.getElementById('teksNamaHapusVar').innerText = nama;

                const linkHapus = document.getElementById('linkHapusVarModal');
                if (linkHapus) {
                    linkHapus.href = "<?= base_url('bot-global/delete/') ?>" + id;
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>