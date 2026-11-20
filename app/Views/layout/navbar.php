<nav class="app-header navbar navbar-expand bg-body" aria-label="Navigasi atas">
    <div class="container-fluid navbar-inner">
        <div class="navbar-leading">
            <button class="btn sidebar-toggle-button" type="button" data-lte-toggle="sidebar"
                aria-label="Buka atau tutup navigasi" title="Buka/tutup sidebar">
                <span class="hamburger-mark" aria-hidden="true"><span></span><span></span><span></span></span>
                <span class="visually-hidden">Buka atau tutup navigasi</span>
            </button>
            <div class="topbar-context d-none d-sm-flex" aria-label="BOSSCUAN Admin Console">
                <span class="topbar-context-brand">BOSSCUAN</span>
                <span class="topbar-context-divider" aria-hidden="true">/</span>
                <span class="topbar-context-label">Admin Console</span>
            </div>
        </div>

        <div class="navbar-actions">
            <!-- <div class="dropdown">
                <button class="btn navbar-icon-button" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Buka pusat notifikasi" title="Notifikasi">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notificationDropdown">
                    <li class="notification-menu-header">
                        <div>
                            <strong>Pusat notifikasi</strong>
                            <small>Informasi dan aktivitas terbaru</small>
                        </div>
                        <i class="bi bi-bell text-primary" aria-hidden="true"></i>
                    </li>
                    <li>
                        <div class="notification-empty-state">
                            <span class="notification-empty-icon"><i class="bi bi-bell-slash" aria-hidden="true"></i></span>
                            <strong>Belum ada item untuk ditampilkan</strong>
                            <small>Gunakan pintasan di bawah untuk meninjau aktivitas.</small>
                        </div>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item notification-link" href="<?= base_url('AdminDashboard/memberLogs') ?>">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            <span>Aktivitas member</span>
                            <i class="bi bi-arrow-up-right ms-auto notification-link-arrow" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item notification-link" href="<?= base_url('activity-reminders') ?>">
                            <i class="bi bi-person-check" aria-hidden="true"></i>
                            <span>Evaluasi aktivitas</span>
                            <i class="bi bi-arrow-up-right ms-auto notification-link-arrow" aria-hidden="true"></i>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item notification-link" href="<?= base_url('chat-logs') ?>">
                            <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                            <span>Log obrolan Telegram</span>
                            <i class="bi bi-arrow-up-right ms-auto notification-link-arrow" aria-hidden="true"></i>
                        </a>
                    </li>
                </ul>
            </div> -->

            <div class="dropdown">
                <button class="btn user-menu-button dropdown-toggle d-flex align-items-center" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="user-menu-avatar"><i class="bi bi-person-fill" aria-hidden="true"></i></span>
                    <span
                        class="user-menu-name d-none d-sm-inline"><?= esc(session()->get('nama') ?? 'Administrator') ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end user-menu-dropdown">
                    <li class="dropdown-header">
                        <span class="user-menu-avatar user-menu-avatar-lg"><i class="bi bi-person-fill"
                                aria-hidden="true"></i></span>
                        <span
                            class="d-block fw-semibold mt-2"><?= esc(session()->get('nama') ?? 'Administrator') ?></span>
                        <small class="text-body-secondary">Administrator</small>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="<?= base_url('auth/logout') ?>">
                            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Keluar dari sistem
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>