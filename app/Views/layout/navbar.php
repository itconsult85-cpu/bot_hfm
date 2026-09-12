<nav class="navbar navbar-expand bg-white border-bottom sticky-top py-3 shadow-sm">
    <div class="container d-flex align-items-center">
        <button class="btn btn-light border-0 me-3 d-flex align-items-center justify-content-center" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" style="width: 45px; height: 45px; border-radius: 12px;">
            <i class="bi bi-list fs-4"></i>
        </button>

        <a class="navbar-brand text-primary fw-bold fs-4 me-auto" href="<?= base_url('AdminDashboard') ?>">
            <i class="bi bi-graph-up-arrow me-2"></i>BOSSCUAN VIP
        </a>

        <div class="dropdown">
            <button class="btn btn-light border rounded-pill px-3 py-1 dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-2 text-primary fs-5"></i>
                <span class="d-none d-sm-inline fw-semibold"><?= session()->get('nama') ?? 'Administrator' ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end mt-2 shadow border-0" style="border-radius: 12px;">
                <li>
                    <a class="dropdown-item text-danger fw-semibold py-2" href="<?= base_url('auth/logout') ?>">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>