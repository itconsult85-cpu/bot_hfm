<?php

namespace App\Controllers;

use CodeIgniter\Database\Exceptions\DatabaseException;

class MtprotoGroupMembers extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'Anggota Telegram Belum Tercatat',
            'members' => [],
            'scannedTotal' => 0,
            'unrecordedTotal' => 0,
            'groupName' => '',
            'groupId' => '',
            'search' => trim((string) $this->request->getGet('search')),
            'error' => null,
            'readOnly' => true,
        ];

        $token = $this->getBotControlToken();
        if ($token === '') {
            $data['error'] = 'BOT_CONTROL_TOKEN belum dikonfigurasi.';
            return view('mtproto_group_members/index', $data);
        }

        $host = trim((string) (getenv('BOT_API_HOST') ?: '127.0.0.1'));
        $port = (int) (getenv('API_PORT') ?: 3000);
        if ($port < 1 || $port > 65535) {
            $port = 3000;
        }

        $url = sprintf('http://%s:%d/api/mtproto-group-members', $host, $port);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['X-Bot-Control-Token: ' . $token],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 180,
        ]);
        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError || $httpCode < 200 || $httpCode >= 300) {
            $response = json_decode($raw ?: '', true);
            $data['error'] = $curlError ?: (string) ($response['error'] ?? 'Bot MTProto gagal membaca anggota grup.');
            return view('mtproto_group_members/index', $data);
        }

        $response = json_decode($raw ?: '', true);
        if (!is_array($response) || ($response['status'] ?? '') !== 'success') {
            $data['error'] = (string) ($response['error'] ?? 'Respons bot MTProto tidak valid.');
            return view('mtproto_group_members/index', $data);
        }

        $data['groupName'] = (string) ($response['group_name'] ?? '');
        $data['groupId'] = (string) ($response['group_id'] ?? '');
        $liveMembers = is_array($response['members'] ?? null) ? $response['members'] : [];
        $data['scannedTotal'] = count($liveMembers);

        try {
            $knownIds = $this->loadKnownTelegramIds($data['groupId']);
            foreach ($liveMembers as $member) {
                $telegramId = trim((string) ($member['telegram_id'] ?? ''));
                if ($telegramId === '' || isset($knownIds[$telegramId])) {
                    continue;
                }

                $search = strtolower($data['search']);
                $haystack = strtolower(implode(' ', [
                    $telegramId,
                    (string) ($member['username'] ?? ''),
                    (string) ($member['first_name'] ?? ''),
                    (string) ($member['last_name'] ?? ''),
                ]));
                if ($search !== '' && strpos($haystack, $search) === false) {
                    continue;
                }

                $data['members'][] = [
                    'telegram_id' => $telegramId,
                    'username' => $member['username'] ?? null,
                    'first_name' => $member['first_name'] ?? null,
                    'last_name' => $member['last_name'] ?? null,
                    'is_bot' => !empty($member['is_bot']),
                    'is_deleted' => !empty($member['is_deleted']),
                ];
            }
            $data['unrecordedTotal'] = count($data['members']);
        } catch (DatabaseException $exception) {
            $data['error'] = 'Database tidak dapat membaca daftar ID Telegram yang sudah tercatat: ' . $exception->getMessage();
        }

        return view('mtproto_group_members/index', $data);
    }

    private function getBotControlToken(): string
    {
        try {
            $row = \Config\Database::connect()->table('bot_globals')
                ->select('key_value')
                ->where('key_name', 'BOT_CONTROL_TOKEN')
                ->orderBy('id', 'DESC')
                ->get()->getRowArray();
            return trim((string) ($row['key_value'] ?? ''));
        } catch (DatabaseException $exception) {
            return '';
        }
    }

    private function loadKnownTelegramIds(string $groupId): array
    {
        $db = \Config\Database::connect();
        $known = [];
        $addRows = static function (array $rows) use (&$known): void {
            foreach ($rows as $row) {
                $id = trim((string) ($row['id_telegram'] ?? $row['telegram_id'] ?? ''));
                if ($id !== '' && $id !== '-') {
                    $known[$id] = true;
                }
            }
        };

        if ($db->tableExists('tb_member_vip') && $db->fieldExists('id_telegram', 'tb_member_vip')) {
            $addRows($db->table('tb_member_vip')->select('id_telegram')->get()->getResultArray());
        }
        if ($db->tableExists('tb_member_logs') && $db->fieldExists('id_telegram', 'tb_member_logs')) {
            $addRows($db->table('tb_member_logs')->select('id_telegram')->get()->getResultArray());
        }
        if ($db->tableExists('telegram_group_members')) {
            $addRows($db->table('telegram_group_members')->select('telegram_id')->where('group_id', $groupId)->get()->getResultArray());
        }

        return $known;
    }
}
