<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <title><?= $title ?? 'Dashboard BOSSCUAN VIP' ?> - Akademi Full Margin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#212529">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/overlayscrollbars/overlayscrollbars.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/admin-lte/adminlte.min.css') ?>">
    <link rel="stylesheet"
        href="<?= base_url('assets/css/dashboard.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/dashboard.css') ?>">
    <script>
    const APP_ROUTES = {
        hapusMember: "<?= base_url('AdminDashboard/hapus-member/') ?>",
        syncHfm: "<?= base_url('AdminDashboard/syncHfm/') ?>"
    };
    </script>
    <?= $this->renderSection('styles') ?>
</head>

<body class="layout-fixed fixed-header fixed-footer sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <?= $this->include('layout/navbar') ?>
        <?= $this->include('layout/sidebar') ?>

        <main class="app-main">
            <div class="app-content">
                <div class="container-fluid p-3 p-lg-4">
                    <?= $this->renderSection('content') ?>
                </div>
            </div>
        </main>

        <footer class="app-footer">
            <strong>Akademi Full Margin</strong>
            <span class="text-body-secondary ms-auto">Panel Administrasi</span>
        </footer>

        <div class="modal fade" id="appDialog" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="appDialogTitle">Informasi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body text-secondary" id="appDialogMessage"></div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal"
                            id="appDialogCancel">Tutup</button>
                        <button type="button" class="btn btn-primary rounded-pill px-4 d-none"
                            id="appDialogConfirm">Lanjutkan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= base_url('assets/vendor/popper/popper.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/admin-lte/adminlte.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/jquery/jquery-3.7.1.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/datatables/jquery.dataTables.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/datatables/dataTables.bootstrap5.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/chartjs/chart.js') ?>"></script>

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
                appDialogElement.addEventListener('hidden.bs.modal', () => resolve(value), {
                    once: true
                });
                appDialog.hide();
            };
            appDialogConfirm.onclick = () => finish(true);
            appDialogCancel.onclick = () => finish(false);
        });
    }

    window.showAppAlert = message => showAppDialog(message, {
        title: 'Informasi'
    });
    window.showAppConfirm = (message, options = {}) => showAppDialog(message, {
        ...options,
        confirm: true
    });
    window.alert = message => {
        showAppAlert(String(message));
    };

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