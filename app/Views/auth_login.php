<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#212529">
    <title>Login Admin - HFM Bot</title>
    <link rel="stylesheet" href="<?= base_url('assets/vendor/bootstrap-icons/font/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/overlayscrollbars/overlayscrollbars.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/vendor/admin-lte/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/dashboard.css') ?>?v=<?= filemtime(FCPATH . 'assets/css/dashboard.css') ?>">
</head>
<body class="login-page bg-body-tertiary">
    <main class="login-box">
        <div class="card card-outline card-primary mb-0">
            <div class="card-header text-center">
                <h1><b>Admin</b>HFM</h1>
                <p class="text-body-secondary small mb-0 mt-2">Akademi Full Margin</p>
            </div>
            <div class="card-body">
                <p class="login-box-msg">Silakan login untuk mengelola sistem</p>

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i><?= esc(session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('auth/loginProcess') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" autocomplete="username" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" autocomplete="current-password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Masuk ke Admin
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script src="<?= base_url('assets/vendor/popper/popper.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/bootstrap/bootstrap.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js') ?>"></script>
    <script src="<?= base_url('assets/vendor/admin-lte/adminlte.min.js') ?>"></script>
</body>
</html>
