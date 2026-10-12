<?php

namespace App\Controllers;

use CodeIgniter\Database\Exceptions\DatabaseException;

class GroupMembership extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $search = trim((string) $this->request->getGet('search'));
        $platform = strtolower(trim((string) $this->request->getGet('platform')));
        $status = strtolower(trim((string) $this->request->getGet('status')));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = 20;

        $union = "
            SELECT 'telegram' AS platform, telegram_id AS user_id, phone_number, display_name,
                   group_id, group_name, status, joined_at, left_at
            FROM telegram_group_members
            UNION ALL
            SELECT 'whatsapp' AS platform, whatsapp_id AS user_id, phone_number, display_name,
                   wgm.group_id, COALESCE(bgl.group_name, 'WhatsApp') AS group_name,
                   wgm.status, wgm.joined_at, wgm.left_at
            FROM whatsapp_group_members wgm
            LEFT JOIN bot_group_links bgl ON bgl.platform = 'whatsapp' AND bgl.group_id = wgm.group_id
        ";

        $conditions = [];
        $binds = [];
        if (in_array($platform, ['telegram', 'whatsapp'], true)) {
            $conditions[] = 'members.platform = ?';
            $binds[] = $platform;
        } else {
            $platform = '';
        }
        if (in_array($status, ['active', 'left'], true)) {
            $conditions[] = 'members.status = ?';
            $binds[] = $status;
        } else {
            $status = '';
        }
        if ($search !== '') {
            $conditions[] = '(members.user_id LIKE ? OR members.phone_number LIKE ? OR members.display_name LIKE ? OR members.group_id LIKE ? OR members.group_name LIKE ?)';
            $pattern = '%' . $search . '%';
            array_push($binds, $pattern, $pattern, $pattern, $pattern, $pattern);
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $data = [
            'title' => 'Keanggotaan Grup',
            'memberships' => [],
            'search' => $search,
            'platform_filter' => $platform,
            'status_filter' => $status,
            'total' => 0,
            'currentPage' => $page,
            'perPage' => $perPage,
            'pagerLinks' => '',
            'setupError' => null,
        ];

        try {
            $countRow = $db->query("SELECT COUNT(*) AS total FROM ({$union}) AS members{$where}", $binds)->getRowArray();
            $total = (int) ($countRow['total'] ?? 0);
            $totalPages = (int) ceil($total / $perPage);
            $page = $totalPages > 0 ? min($page, $totalPages) : 1;
            $offset = ($page - 1) * $perPage;
            $rows = $db->query(
                "SELECT * FROM ({$union}) AS members{$where} ORDER BY COALESCE(left_at, joined_at) DESC LIMIT {$perPage} OFFSET {$offset}",
                $binds
            )->getResultArray();
            $data['memberships'] = $rows;
            $data['total'] = $total;
            $data['currentPage'] = $page;
            $data['pagerLinks'] = service('pager')->makeLinks($page, $perPage, $total, 'default_full');
        } catch (DatabaseException $e) {
            if ((int) $e->getCode() !== 1146) {
                throw $e;
            }
            $data['setupError'] = 'Tabel pencatatan anggota grup belum tersedia. Jalankan sql/server_schema_sync.sql pada database aplikasi.';
        }

        return view('group_memberships/index', $data);
    }
}
