<?php
$uri = uri_string();

$isBotSettings = strpos($uri, 'bot-global') === 0
    || strpos($uri, 'bot-flow') === 0
    || strpos($uri, 'bot-faq') === 0
    || strpos($uri, 'activity-reminder-phases') === 0
    || strpos($uri, 'bot-schedules') === 0
    || strpos($uri, 'bot-group-links') === 0;
$isBotData = strpos($uri, 'user-progress') === 0
    || strpos($uri, 'group-memberships') === 0
    || strpos($uri, 'mtproto-group-members') === 0
    || strpos($uri, 'chat-logs') === 0;
$isHfmApi = strpos($uri, 'AdminDashboard/overallPerformance') === 0
    || strpos($uri, 'AdminDashboard/clientPerformance') === 0
    || strpos($uri, 'AdminDashboard/clientTrades') === 0
    || strpos($uri, 'AdminDashboard/campaigns') === 0
    || strpos($uri, 'AdminDashboard/campaignWallets') === 0
    || strpos($uri, 'AdminDashboard/campaign-wallets') === 0
    || strpos($uri, 'AdminDashboard/campaignTrades') === 0
    || strpos($uri, 'AdminDashboard/campaignRawClicks') === 0
    || strpos($uri, 'client-acid-code-modification') === 0;
?>

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= base_url('AdminDashboard') ?>" class="brand-link">
            <span class="brand-mark"><i class="bi bi-graph-up-arrow"></i></span>
            <span class="brand-copy">
                <span class="brand-text">BOSSCUAN</span>
                <small>ADMIN CONSOLE</small>
            </span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2" aria-label="Navigasi utama">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="true">
                <li class="nav-header">MENU UTAMA</li>
                <li class="nav-item">
                    <a href="<?= base_url('AdminDashboard') ?>"
                        class="nav-link <?= ($uri === 'AdminDashboard' || $uri === '') ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-grid-1x2-fill"></i>
                        <p>Dashboard IB</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('AdminDashboard/memberLogs') ?>"
                        class="nav-link <?= $uri === 'AdminDashboard/memberLogs' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-clock-history"></i>
                        <p>Aktivitas Member</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('activity-reminders') ?>"
                        class="nav-link <?= strpos($uri, 'activity-reminders') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-person-check-fill"></i>
                        <p>Evaluasi Aktivitas</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= base_url('MediaAdmin') ?>"
                        class="nav-link <?= strpos($uri, 'MediaAdmin') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-images"></i>
                        <p>Kelola Media</p>
                    </a>
                </li>

                <li class="nav-header">LAPORAN BROKER HFM</li>
                <li class="nav-item <?= $isHfmApi ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= $isHfmApi ? 'active' : '' ?>"
                        aria-expanded="<?= $isHfmApi ? 'true' : 'false' ?>">
                        <i class="nav-icon bi bi-pie-chart-fill"></i>
                        <p>HFM API Reports<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= base_url('AdminDashboard/overallPerformance') ?>"
                                class="nav-link <?= strpos($uri, 'AdminDashboard/overallPerformance') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Overall Performance</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('AdminDashboard/clientPerformance') ?>"
                                class="nav-link <?= strpos($uri, 'AdminDashboard/clientPerformance') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Client Performance</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('AdminDashboard/clientTrades') ?>"
                                class="nav-link <?= strpos($uri, 'AdminDashboard/clientTrades') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Client Trades</p>
                            </a>
                        </li>
                        <li class="nav-item nav-submenu-separator" aria-hidden="true">
                            <hr>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('AdminDashboard/campaigns') ?>"
                                class="nav-link <?= strpos($uri, 'AdminDashboard/campaigns') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Daftar Kampanye</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('AdminDashboard/campaign-wallets') ?>"
                                class="nav-link <?= strpos($uri, 'AdminDashboard/campaign-wallets') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Wallet Registrations</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('AdminDashboard/campaignTrades') ?>"
                                class="nav-link <?= strpos($uri, 'AdminDashboard/campaignTrades') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Komisi Kampanye</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('AdminDashboard/campaignRawClicks') ?>"
                                class="nav-link <?= strpos($uri, 'AdminDashboard/campaignRawClicks') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Klik Kampanye</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('client-acid-code-modification') ?>"
                                class="nav-link <?= strpos($uri, 'client-acid-code-modification') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Modifikasi ACID Wallet</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('client-acid-code-modification/trading-account') ?>"
                                class="nav-link <?= strpos($uri, 'client-acid-code-modification/trading-account') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Modifikasi ACID Trading Account</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-header">BOT TELEGRAM</li>
                <li class="nav-item <?= $isBotSettings ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= $isBotSettings ? 'active' : '' ?>"
                        aria-expanded="<?= $isBotSettings ? 'true' : 'false' ?>">
                        <i class="nav-icon bi bi-robot"></i>
                        <p>Pengaturan Bot<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= base_url('bot-global') ?>"
                                class="nav-link <?= strpos($uri, 'bot-global') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Variabel Global</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('bot-flow') ?>"
                                class="nav-link <?= strpos($uri, 'bot-flow') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Alur Pendaftaran</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('bot-faq') ?>"
                                class="nav-link <?= strpos($uri, 'bot-faq') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Tanya Jawab (FAQs)</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('activity-reminder-phases') ?>"
                                class="nav-link <?= strpos($uri, 'activity-reminder-phases') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Fase Pengingat Aktivitas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('bot-schedules') ?>"
                                class="nav-link <?= strpos($uri, 'bot-schedules') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Jadwal Pengiriman &amp; Bot</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('bot-group-links') ?>"
                                class="nav-link <?= strpos($uri, 'bot-group-links') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Link Grup Tele &amp; WhatsApp</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item <?= $isBotData ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= $isBotData ? 'active' : '' ?>"
                        aria-expanded="<?= $isBotData ? 'true' : 'false' ?>">
                        <i class="nav-icon bi bi-database-fill"></i>
                        <p>Data Bot<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= base_url('user-progress') ?>"
                                class="nav-link <?= strpos($uri, 'user-progress') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Progress Pendaftar</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('group-memberships') ?>"
                                class="nav-link <?= strpos($uri, 'group-memberships') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Anggota Grup Tele &amp; WhatsApp</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('mtproto-group-members') ?>"
                                class="nav-link <?= strpos($uri, 'mtproto-group-members') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Anggota Telegram Belum Tercatat</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('chat-logs') ?>"
                                class="nav-link <?= strpos($uri, 'chat-logs') === 0 ? 'active' : '' ?>">
                                <i class="nav-icon bi bi-circle"></i>
                                <p>Log Obrolan Telegram</p>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</aside>