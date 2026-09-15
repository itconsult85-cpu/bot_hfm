<?= $this->extend('layout/template') ?>
<?= $this->section('content') ?>

<div class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mt-2">

    <div class="row align-items-md-center justify-content-between mb-3 g-3">
        <div class="col-12 col-md">
            <h4 class="text-primary fw-bold mb-1" style="font-size: 1.25rem;">Pengaturan Fase Pengingat</h4>
            <p class="text-muted small mb-0" style="font-size: 0.8rem;">Atur jumlah fase, hari setelah bergabung, dan isi pesan Telegram.</p>
        </div>
        <div class="col-12 col-md-auto">
            <button class="btn btn-primary rounded-pill w-100 px-4 py-2 small fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#phaseModal" onclick="newPhase()">
                <i class="bi bi-plus-lg me-1"></i> Tambah Fase
            </button>
        </div>
    </div>

    <?php if ($msg = session()->getFlashdata('pesan')): ?>
        <div class="alert alert-success py-2 small shadow-sm"><i class="bi bi-check-circle-fill me-2"></i><?= esc($msg) ?></div>
    <?php endif; ?>

    <div class="alert alert-light border small py-2 mb-3" style="font-size: 0.75rem;">
        Fase aktif akan diproses tepat pada jumlah hari setelah <code class="bg-light">created_at</code>. Anda bebas menghapus, menambah, atau mengubah urutannya berdasarkan hari.
    </div>

    <div class="table-responsive border-0 overflow-hidden">
        <table class="table table-hover align-middle table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center border-bottom-0 px-1 px-md-3" style="width: 45px;">#</th>
                    <th class="border-bottom-0 d-none d-md-table-cell">Key</th>
                    <th class="border-bottom-0 px-1 px-md-2">Nama Fase</th>
                    <th class="text-center border-bottom-0 d-none d-md-table-cell">Hari ke-</th>
                    <th class="border-bottom-0 px-1 px-md-2 text-center text-md-start">Status</th>
                    <th class="border-bottom-0 d-none d-md-table-cell" style="width: 35%;">Pesan</th>
                    <th class="text-center border-bottom-0 px-1 px-md-3" style="width: 90px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1;
                foreach ($phases as $phase): ?>

                    <tr>
                        <td class="text-center px-1 px-md-3">
                            <button class="btn btn-sm btn-light border rounded-circle d-md-none shadow-sm fw-bold p-1 d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; line-height: 1;" type="button" data-bs-toggle="collapse" data-bs-target="#expandPhase<?= $phase['id'] ?>" aria-expanded="false">
                                <i class="bi bi-chevron-down text-primary" style="font-size: 0.75rem;"></i>
                            </button>
                            <span class="text-muted small fw-semibold d-none d-md-inline"><?= $no++ ?></span>
                        </td>

                        <td class="d-none d-md-table-cell">
                            <code><?= esc($phase['phase_key']) ?></code>
                        </td>

                        <td class="py-2 px-1 px-md-2">
                            <span class="fw-semibold text-dark d-block" style="word-break: break-word; font-size: 0.9rem;">
                                <?= esc($phase['phase_name']) ?>
                            </span>
                        </td>

                        <td class="d-none d-md-table-cell text-center text-muted fw-semibold">
                            <?= esc($phase['days_after_join']) ?>
                        </td>

                        <td class="px-1 px-md-2 text-center text-md-start">
                            <?= $phase['is_active'] ? '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1">Aktif</span>' : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2 py-1">Nonaktif</span>' ?>
                        </td>

                        <td class="d-none d-md-table-cell">
                            <div class="bg-light p-2 border rounded text-muted" style="white-space: pre-wrap; max-height: 70px; overflow-y: auto; font-size: 0.75rem;">
                                <?= esc($phase['message']) ?>
                            </div>
                        </td>

                        <td class="text-center px-1 px-md-3">
                            <div class="d-flex gap-1 justify-content-center">
                                <button class="btn btn-sm btn-light border rounded-circle p-1 p-md-2 d-inline-flex align-items-center justify-content-center" style="width: 28px; height: 28px;" onclick='editPhase(<?= json_encode($phase, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)' title="Edit Fase">
                                    <i class="bi bi-pencil text-warning" style="font-size: 0.8rem;"></i>
                                </button>

                                <button type="button" class="btn btn-sm btn-light border rounded-circle p-1 p-md-2 d-inline-flex align-items-center justify-content-center btn-hapus-fase" style="width: 28px; height: 28px;"
                                    data-id="<?= $phase['id'] ?>"
                                    data-nama="<?= esc($phase['phase_name']) ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalHapusFase" title="Hapus Fase">
                                    <i class="bi bi-trash text-danger" style="font-size: 0.8rem;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr id="expandPhase<?= $phase['id'] ?>" class="collapse d-md-none bg-light border-bottom">
                        <td colspan="4" class="p-3 shadow-inner">
                            <ul class="list-unstyled mb-0 small px-2">
                                <li class="mb-2">
                                    <span class="fw-semibold d-block text-muted mb-1">Key Sistem</span>
                                    <code><?= esc($phase['phase_key']) ?></code>
                                </li>
                                <li class="mb-2">
                                    <span class="fw-semibold d-block text-muted mb-1">Hari Setelah Bergabung</span>
                                    <span class="badge bg-white text-dark border px-2 py-1">Hari ke-<?= esc($phase['days_after_join']) ?></span>
                                </li>
                                <li>
                                    <span class="fw-semibold d-block text-muted mb-1">Isi Pesan Telegram</span>
                                    <div class="bg-white p-2 border rounded" style="white-space: pre-wrap; font-size: 0.75rem;">
                                        <?= esc($phase['message']) ?>
                                    </div>
                                </li>
                            </ul>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="phaseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0">
            <form id="phaseForm" method="post">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold mb-0 modal-title"><i class="bi bi-envelope-paper text-primary me-2"></i>Fase Pengingat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted">Key</label>
                            <input class="form-control" name="phase_key" id="phase_key" placeholder="Contoh: h3, final" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold text-muted">Nama Fase</label>
                            <input class="form-control" name="phase_name" id="phase_name" placeholder="Contoh: H-3 Reminder" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted">Hari Setelah Gabung</label>
                            <input class="form-control" type="number" min="0" name="days_after_join" id="days_after_join" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Pesan Telegram</label>
                            <textarea class="form-control" name="message" id="message" rows="8" placeholder="Tuliskan isi pesan pengingat..." required></textarea>
                            <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Dukung teks biasa. Bot akan membaca variabel jika diatur.</small>
                        </div>
                        <div class="col-12 ms-1 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked style="cursor: pointer;">
                                <label class="form-check-label small fw-semibold text-dark" for="is_active">Aktifkan Fase Ini</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary rounded-pill px-4 fw-semibold" type="submit">Simpan Fase</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalHapusFase" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-body p-4 text-center">
                <div class="display-6 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <h5 class="fw-bold mb-2">Hapus Fase?</h5>
                <p class="text-muted small">Apakah Anda yakin ingin menghapus fase pengingat <br>
                    <strong class="text-dark" id="teksNamaHapusFase"></strong> secara permanen?
                </p>
                <div class="d-grid gap-2 mt-4">
                    <a href="#" id="linkHapusFaseModal" class="btn btn-danger rounded-pill py-2 fw-semibold">
                        <i class="bi bi-trash3 me-2"></i>Ya, Hapus Fase
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
    const basePhase = '<?= base_url('activity-reminder-phases') ?>';

    function newPhase() {
        document.getElementById('phaseForm').action = basePhase + '/store';
        document.getElementById('phaseForm').reset();
        document.getElementById('is_active').checked = true;
    }

    function editPhase(p) {
        document.getElementById('phaseForm').action = basePhase + '/update/' + p.id;
        for (const k of ['phase_key', 'phase_name', 'days_after_join', 'message']) {
            document.getElementById(k).value = p[k];
        }
        document.getElementById('is_active').checked = Number(p.is_active) === 1;
        new bootstrap.Modal(document.getElementById('phaseModal')).show();
    }

    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll('.btn-hapus-fase').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const nama = this.getAttribute('data-nama');

                document.getElementById('teksNamaHapusFase').innerText = nama;

                const linkHapus = document.getElementById('linkHapusFaseModal');
                if (linkHapus) {
                    linkHapus.href = basePhase + '/delete/' + id;
                }
            });
        });
    });
</script>
<?= $this->endSection() ?>