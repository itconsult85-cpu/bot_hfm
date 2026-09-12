<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>VIP Console - HFM Manager</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #f8fafc;
            font-family: 'Inter', system-ui, sans-serif;
            color: #0f172a;
        }

        .navbar-brand {
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .data-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            transition: all 0.3s ease;
        }

        .data-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }

        .avatar-sub {
            width: 48px;
            height: 48px;
            background: #eff6ff;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #2563eb;
            font-size: 1.2rem;
        }

        .modal-content {
            border-radius: 20px;
            border: none;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 0.6rem 1rem;
        }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom sticky-top py-3 shadow-sm">
        <div class="container">
            <span class="navbar-brand text-primary fs-4"><i class="bi bi-graph-up-arrow me-2"></i>VIP Console</span>
            <a href="<?= base_url('logout') ?>" class="btn btn-outline-danger btn-sm rounded-pill px-4 fw-semibold"><i class="bi bi-box-arrow-right me-1"></i>Keluar</a>
        </div>
    </nav>

    <div class="container py-5">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold tracking-tight mb-1">Manajemen Member VIP</h3>
                <p class="text-muted small mb-0">Total Member Terdata: <span class="fw-bold text-dark"><?= count($members) ?></span> Akun</p>
            </div>
            <!-- Notifikasi Flashdata (Jika ada pesan sukses update/delete) -->
            <?php if (session()->getFlashdata('pesan')): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-pill px-4 py-2" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('pesan') ?>
                    <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        </div>

        <!-- LIST DATA CARD -->
        <div class="row justify-content-center g-3">
            <?php foreach ($members as $m): ?>
                <div class="col-lg-10 col-12">
                    <div class="card data-card p-3 shadow-sm">
                        <div class="row align-items-center g-3">

                            <!-- Avatar & Info Utama -->
                            <div class="col-auto">
                                <div class="avatar-sub"><i class="bi bi-person-fill"></i></div>
                            </div>
                            <div class="col-md-3 col-12">
                                <h6 class="fw-bold mb-0 text-dark">ID: <?= esc($m['id_hfm']) ?></h6>
                                <span class="text-muted small"><i class="bi bi-whatsapp me-1 text-success"></i><?= esc($m['no_wa']) ?></span>
                            </div>

                            <!-- Info Deposit -->
                            <div class="col-md-2 col-6">
                                <p class="mb-0 text-muted" style="font-size: 0.75rem;">Deposit</p>
                                <span class="fw-bold text-dark">
                                    <!-- Tampilkan Currency di Sini -->
                                    <?= esc($m['currency'] ?? 'USD') ?> <?= number_format($m['deposit'], 2) ?>
                                </span>
                            </div>

                            <!-- Status Aktif / Lepas IB -->
                            <div class="col-md-2 col-6">
                                <?php if ($m['status'] === 'aktif'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 small fw-semibold"><i class="bi bi-check-circle me-1"></i>Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 small fw-semibold"><i class="bi bi-x-circle me-1"></i>Lepas IB</span>
                                <?php endif; ?>
                            </div>

                            <!-- Status Last Trade -->
                            <div class="col-md-2 col-6">
                                <?php if (empty($m['last_trade']) || $m['last_trade'] === '0000-00-00 00:00:00'): ?>
                                    <span class="badge bg-light text-muted border rounded-pill px-3 py-2 small fw-semibold"><i class="bi bi-clock-history me-1"></i>Belum Trade</span>
                                <?php else: ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-2 small fw-semibold"><i class="bi bi-graph-up me-1"></i><?= date('d M Y', strtotime($m['last_trade'])) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Tombol Aksi (Update & Delete) -->
                            <div class="col-md-2 col-12 text-md-end text-start">
                                <button class="btn btn-light btn-sm rounded-circle p-2 me-1 btn-edit shadow-sm border"
                                    data-id="<?= $m['id_hfm'] ?>"
                                    data-wa="<?= $m['no_wa'] ?>"
                                    data-currency="<?= $m['currency'] ?? 'USD' ?>"
                                    data-deposit="<?= $m['deposit'] ?>"
                                    data-status="<?= $m['status'] ?>"
                                    data-trade="<?= $m['last_trade'] ?>"
                                    title="Edit Data">
                                    <i class="bi bi-pencil-square text-primary"></i>
                                </button>
                                <button class="btn btn-light btn-sm rounded-circle p-2 btn-hapus shadow-sm border"
                                    data-id="<?= $m['id_hfm'] ?>"
                                    title="Hapus Data">
                                    <i class="bi bi-trash3-fill text-danger"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- MODAL EDIT DATA -->
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold mb-0">Edit Data Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="<?= base_url('admin/update-member') ?>" method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">ID HFM (Read Only)</label>
                            <input type="text" class="form-control bg-light" id="edit_id" name="id_hfm" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Nomor WA</label>
                            <input type="text" class="form-control" id="edit_wa" name="no_wa" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="form-label small fw-semibold text-muted">Mata Uang</label>
                                <select class="form-select" id="edit_currency" name="currency">
                                    <option value="USD">USD</option>
                                    <option value="USC">USC</option>
                                    <option value="IDR">IDR</option>
                                </select>
                            </div>
                            <div class="col-8">
                                <label class="form-label small fw-semibold text-muted">Nominal Deposit</label>
                                <input type="number" step="0.01" class="form-control" id="edit_deposit" name="deposit" required>
                            </div>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted">Status</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="aktif">Aktif</option>
                                    <option value="lepas_ib">Lepas IB</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-muted">Last Trade</label>
                                <input type="text" class="form-control" id="edit_trade" name="last_trade" placeholder="YYYY-MM-DD HH:MM:SS">
                                <small class="text-muted" style="font-size: 0.7rem;">Isi 0000-00-00 00:00:00 jika belum trade</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL HAPUS DATA -->
    <div class="modal fade" id="modalHapus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content shadow-lg">
                <div class="modal-body p-4 text-center">
                    <div class="display-6 text-danger mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <h5 class="fw-bold mb-2">Hapus Member?</h5>
                    <p class="text-muted small">Apakah Anda yakin ingin menghapus data dengan ID <strong class="text-dark" id="teksIdHapus"></strong> secara permanen?</p>

                    <div class="d-grid gap-2 mt-4">
                        <a href="#" id="linkHapusModal" class="btn btn-danger rounded-pill py-2 fw-semibold">Ya, Hapus Permanen</a>
                        <button type="button" class="btn btn-link text-muted btn-sm text-decoration-none" data-bs-dismiss="modal">Batalkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPT BOOTSTRAP & JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Logika Modal Edit
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.getAttribute('data-id');
                document.getElementById('edit_wa').value = this.getAttribute('data-wa');
                document.getElementById('edit_currency').value = this.getAttribute('data-currency'); // <--- TAMBAHAN
                document.getElementById('edit_deposit').value = this.getAttribute('data-deposit');
                document.getElementById('edit_status').value = this.getAttribute('data-status');
                document.getElementById('edit_trade').value = this.getAttribute('data-trade');
                new bootstrap.Modal(document.getElementById('modalEdit')).show();
            });
        });

        // Logika Modal Hapus
        document.querySelectorAll('.btn-hapus').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                document.getElementById('teksIdHapus').innerText = id;
                document.getElementById('linkHapusModal').setAttribute('href', `<?= base_url('admin/hapus-member/') ?>${id}`);
                new bootstrap.Modal(document.getElementById('modalHapus')).show();
            });
        });
    </script>
</body>

</html>