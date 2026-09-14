<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Dashboard BOSSCUAN VIP' ?> - Akademi Full Margin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        .sidebar-menu-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            border-radius: 10px;
            margin: 4px 12px;
            transition: all 0.2s ease;
        }

        .sidebar-menu-link:hover,
        .sidebar-menu-link.active {
            background-color: #f1f5f9;
            color: #0d6efd;
        }

        .sidebar-menu-link i {
            font-size: 1.1rem;
            width: 30px;
        }

        .submenu-container {
            background-color: #f8fafc;
            border-radius: 10px;
            margin: 0 12px;
            padding: 8px 0;
        }

        .submenu-link {
            display: block;
            padding: 8px 20px 8px 50px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }

        .submenu-link:hover,
        .submenu-link.active {
            color: #0d6efd;
        }

        .menu-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
            padding: 16px 20px 8px;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
    <script>
        const APP_ROUTES = {
            hapusMember: "<?= base_url('AdminDashboard/hapus-member/') ?>",
            syncHfm: "<?= base_url('AdminDashboard/syncHfm/') ?>"
        };
    </script>
    <?= $this->renderSection('styles') ?>
</head>

<body>
    <?= $this->include('layout/navbar') ?>
    <?= $this->include('layout/sidebar') ?>

    <div class="container py-4 flex-grow-1 d-flex flex-column h-100">
        <?= $this->renderSection('content') ?>
    </div>

    <div class="modal fade" id="appDialog" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="appDialogTitle">Informasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body text-secondary" id="appDialogMessage"></div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" id="appDialogCancel">Tutup</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4 d-none" id="appDialogConfirm">Lanjutkan</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const appDialogElement = document.getElementById('appDialog');
        const appDialog = appDialogElement ? new bootstrap.Modal(appDialogElement) : null;
        const appDialogTitle = document.getElementById('appDialogTitle');
        const appDialogMessage = document.getElementById('appDialogMessage');
        const appDialogConfirm = document.getElementById('appDialogConfirm');
        const appDialogCancel = document.getElementById('appDialogCancel');

        function showAppDialog(message, options = {}) {
            if (!appDialog) return Promise.resolve(false);
            appDialogTitle.textContent = options.title || 'Informasi';
            appDialogMessage.textContent = message;
            appDialogConfirm.textContent = options.confirmText || 'Lanjutkan';
            appDialogConfirm.classList.toggle('d-none', !options.confirm);
            appDialogCancel.textContent = options.confirm ? 'Batal' : 'Tutup';
            appDialogConfirm.classList.toggle('btn-danger', options.danger === true);
            appDialogConfirm.classList.toggle('btn-primary', options.danger !== true);
            appDialog.show();
            return new Promise(resolve => {
                const finish = value => {
                    appDialogConfirm.onclick = null;
                    appDialogElement.addEventListener('hidden.bs.modal', () => resolve(value), { once: true });
                    appDialog.hide();
                };
                appDialogConfirm.onclick = () => finish(true);
                appDialogCancel.onclick = () => finish(false);
            });
        }

        window.showAppAlert = message => showAppDialog(message, { title: 'Informasi' });
        window.showAppConfirm = (message, options = {}) => showAppDialog(message, { ...options, confirm: true });
        window.alert = message => { showAppAlert(String(message)); };

        document.addEventListener('click', event => {
            const trigger = event.target.closest('[data-confirm]');
            if (!trigger || trigger.dataset.confirmBound === '1') return;
            event.preventDefault();
            trigger.dataset.confirmBound = '1';
            showAppConfirm(trigger.dataset.confirm, {
                title: trigger.dataset.confirmTitle || 'Konfirmasi tindakan',
                confirmText: trigger.dataset.confirmButton || 'Ya, lanjutkan',
                danger: true
            }).then(approved => {
                trigger.dataset.confirmBound = '0';
                if (approved && trigger.href) window.location.href = trigger.href;
            });
        });
    </script>

    <?= $this->renderSection('scripts') ?>
</body>

</html>
