<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Auth::index');

$routes->get('/update-last-trade', 'Client::updateSemuaLastTrade');
$routes->get('update-currency', 'Client::updateSemuaCurrency');
$routes->get('updateSemuaNama', 'Client::updateSemuaNama');

// Rute Login
$routes->get('auth', 'Auth::index');
$routes->post('auth/loginProcess', 'Auth::loginProcess');
$routes->get('logout', 'Auth::logout');
$routes->get('auth/logout', 'Auth::logout');

// Rute Admin yang Dilindungi (Pakai Filter Auth)
$routes->group('', ['filter' => 'auth'], function ($routes) {
    // Media Admin
    $routes->get('MediaAdmin', 'MediaAdmin::index');
    $routes->post('MediaAdmin/upload', 'MediaAdmin::upload');
    $routes->post('MediaAdmin/update', 'MediaAdmin::update');
    $routes->post('MediaAdmin/hapus', 'MediaAdmin::hapus');

    // Admin Dashboard
    $routes->get('AdminDashboard', 'AdminDashboard::index');
    $routes->get('AdminDashboard/syncHfm/(:any)', 'AdminDashboard::syncHfm/$1');
    $routes->post('AdminDashboard/update-member', 'AdminDashboard::updateMember');
    $routes->post('admin/update-member', 'AdminDashboard::updateMember');
    $routes->post('admin/update-id-quick', 'AdminDashboard::updateIdQuick');
    $routes->post('admin/update-member-ajax', 'AdminDashboard::updateMemberAjax');

    // ROUTE HAPUS MEMBER
    $routes->get('AdminDashboard/hapus-member/(:any)', 'AdminDashboard::hapusMember/$1');

    $routes->get('AdminDashboard/campaigns', 'AdminDashboard::campaigns');
    $routes->get('AdminDashboard/campaign-wallets', 'AdminDashboard::campaignWallets');
    $routes->get('AdminDashboard/campaign-wallets/(:any)', 'AdminDashboard::campaignWallets/$1');

    $routes->get('admin/member-logs', 'AdminDashboard::memberLogs');
    $routes->get('AdminDashboard/memberLogs', 'AdminDashboard::memberLogs');

    $routes->group('bot-faq', function ($routes) {
        $routes->get('/', 'BotFaq::index');
        $routes->get('create', 'BotFaq::create');
        $routes->post('store', 'BotFaq::store');
        $routes->get('edit/(:num)', 'BotFaq::edit/$1');
        $routes->post('update/(:num)', 'BotFaq::update/$1');
        $routes->get('delete/(:num)', 'BotFaq::delete/$1');
    });

    $routes->group('bot-global', function ($routes) {
        $routes->get('/', 'BotGlobal::index');
        $routes->get('create', 'BotGlobal::create');
        $routes->post('store', 'BotGlobal::store');
        $routes->get('edit/(:num)', 'BotGlobal::edit/$1');
        $routes->post('update/(:num)', 'BotGlobal::update/$1');
        $routes->get('delete/(:num)', 'BotGlobal::delete/$1');
    });

    $routes->group('bot-flow', function ($routes) {
        $routes->get('/', 'BotFlow::index');
        $routes->get('create', 'BotFlow::create');
        $routes->post('store', 'BotFlow::store');
        $routes->get('edit/(:num)', 'BotFlow::edit/$1');
        $routes->post('update/(:num)', 'BotFlow::update/$1');
        $routes->get('delete/(:num)', 'BotFlow::delete/$1');
    });

    $routes->group('user-progress', function ($routes) {
        $routes->get('/', 'UserProgress::index');
        $routes->get('delete/(:any)', 'UserProgress::delete/$1');
    });

    $routes->group('chat-logs', function ($routes) {
        $routes->get('/', 'ChatLogTele::index');
        $routes->get('delete/(:num)', 'ChatLogTele::delete/$1');
        $routes->get('clear-all', 'ChatLogTele::clearAll'); // Tombol hapus semua log
    });

    // Tambahkan baris ini di dalam app/Config/Routes.php
    $routes->get('chat-logs/getDetailChat/(:any)', 'ChatLogTele::getDetailChat/$1');
    // ==========================================
    // TAMBAHKAN ROUTE HAPUS LOG DI SINI
    // ==========================================
    $routes->get('AdminDashboard/hapusLog/(:num)', 'AdminDashboard::hapusLog/$1');
    $routes->get('AdminDashboard/backupDatabase', 'AdminDashboard::backupDatabase');
});

// ==========================================================
// JALUR API KHUSUS UNTUK BOT WHATSAPP (NODE.JS)
// ==========================================================
$routes->get('client', 'Client::index');
$routes->get('client/check_status_vip/(:any)', 'Client::check_status_vip/$1');
$routes->get('client/getPanduanMedia/(:any)', 'Client::getPanduanMedia/$1');
$routes->get('client/cekHfm/(:any)/(:any)', 'Client::cekHfm/$1/$2');
$routes->get('client/apiGetPasif', 'Client::apiGetPasif');
$routes->get('client/apiMemberKeluarIB', 'Client::apiMemberKeluarIB');
$routes->post('client/prosesFollowUp', 'Client::prosesFollowUp');
$routes->get('client/apiHapusMemberGrup/(:any)', 'Client::apiHapusMemberGrup/$1');
$routes->get('client/apiLaporanHarian', 'Client::apiLaporanHarian');
$routes->post('client/simpanMemberForm', 'Client::simpanMemberForm');
$routes->get('fix-uang', 'AdminDashboard::updateSemuaCurrency');
