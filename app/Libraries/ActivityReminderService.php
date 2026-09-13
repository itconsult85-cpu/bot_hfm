<?php

namespace App\Libraries;

use Config\Database;
use DateTime;
use DateTimeZone;

class ActivityReminderService
{
    private const HFM_API_KEY = '127e07f2-3b2a-4cb5-9a5b-0610e4ecc86e';
    private const HFM_BASE_URL = 'https://api.hfm-partners.com/api/clients/';
    public function eligibleMembers(?string $phase = null): array
    {
        $members = Database::connect()->table('tb_member_vip')
            ->where('status', 'aktif')
            ->where('id_telegram IS NOT NULL', null, false)
            ->where('id_telegram !=', '')
            ->where('id_telegram !=', '-')
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        $today = new DateTime('today', new DateTimeZone('Asia/Jakarta'));
        $result = [];
        foreach ($members as $member) {
            if (empty($member['created_at'])) {
                continue;
            }
            $member['api_last_trade'] = $this->normaliseDate($member['last_trade'] ?? null);
            $member['last_trade_display'] = $member['api_last_trade']
                ? date('d M Y H:i', strtotime($member['api_last_trade'])) : 'Belum Trading';
            $joined = new DateTime(substr($member['created_at'], 0, 10), new DateTimeZone('Asia/Jakarta'));
            $days = (int) $joined->diff($today)->format('%r%a');
            $memberPhase = null;
            foreach ($this->phases() as $configuredPhase) {
                if ((int) $configuredPhase['days_after_join'] === $days) {
                    $memberPhase = $configuredPhase['phase_key'];
                    break;
                }
            }
            if ($memberPhase === null || ($phase !== null && $memberPhase !== $phase)) {
                continue;
            }
            // Hanya member yang menurut API HFM belum pernah trading.
            if ($member['api_last_trade'] !== null) {
                continue;
            }
            $member['reminder_phase'] = $memberPhase;
            $member['days_since_join'] = $days;
            $result[] = $member;
        }
        return $result;
    }

    /** Mengambil member aktif yang tidak trading minimal N hari dari API HFM. */
    public function inactiveMembersFromApi(int $minimumDays = 30): array
    {
        $members = Database::connect()->table('tb_member_vip')
            ->where('status', 'aktif')
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();
        $today = new DateTime('today', new DateTimeZone('Asia/Jakarta'));
        $result = [];
        foreach ($members as $member) {
            $member['api_last_trade'] = $this->normaliseDate($member['last_trade'] ?? null);
            $member['last_trade_display'] = $member['api_last_trade']
                ? date('d M Y H:i', strtotime($member['api_last_trade'])) : 'Belum Trading';
            $reference = $member['api_last_trade'] ?? $member['created_at'];
            if (!$reference) {
                continue;
            }
            $lastActivity = new DateTime(substr($reference, 0, 10), new DateTimeZone('Asia/Jakarta'));
            $days = (int) $lastActivity->diff($today)->format('%r%a');
            if ($days >= $minimumDays) {
                $member['inactive_days_api'] = $days;
                $result[] = $member;
            }
        }
        return $result;
    }

    public function sendDue(?string $phase = null): array
    {
        $db = Database::connect();
        $due = $this->eligibleMembers($phase);
        $token = $this->getGlobal($db, 'TELEGRAM_TOKEN');
        $adminId = $this->getGlobal($db, 'ID_ADMIN');
        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];
        $sentMembers = [];

        foreach ($due as $member) {
            $memberPhase = $member['reminder_phase'];
            $alreadySent = $db->table('activity_reminder_logs')
                ->where('member_id', $member['id'])
                ->where('phase', $memberPhase)
                ->countAllResults() > 0;
            if ($alreadySent) {
                $skipped++;
                continue;
            }
            $phase = $this->phaseByKey($memberPhase);
            if (!$phase) {
                $failed++;
                $errors[] = $member['id_hfm'] . ': fase tidak ditemukan';
                continue;
            }
            $response = $this->sendTelegram($token, $member['id_telegram'], $phase['message']);
            if ($response['ok'] ?? false) {
                $db->table('activity_reminder_logs')->insert([
                    'member_id' => $member['id'],
                    'phase' => $memberPhase,
                    'telegram_id' => (string) $member['id_telegram'],
                    'sent_at' => date('Y-m-d H:i:s'),
                ]);
                $sent++;
                $sentMembers[] = $member;
            } else {
                $failed++;
                $errors[] = ($member['nama'] ?: $member['id_hfm']) . ': ' . ($response['description'] ?? 'Telegram gagal');
            }
        }
        $result = ['due' => count($due), 'sent' => $sent, 'skipped' => $skipped, 'failed' => $failed, 'errors' => $errors];
        $this->sendAdminReport($token, $adminId, $result, $sentMembers);
        return $result;
    }

    public function message(string $phase): string
    {
        return (string) ($this->phaseByKey($phase)['message'] ?? '');
    }

    public function phases(): array
    {
        return Database::connect()->table('activity_reminder_phases')
            ->where('is_active', 1)->orderBy('days_after_join', 'ASC')->get()->getResultArray();
    }

    public function syncAllHfm(): array
    {
        $db = Database::connect();
        $members = $db->table('tb_member_vip')->select('id,id_hfm')->get()->getResultArray();
        $updated = 0; $failed = 0;
        foreach (array_chunk($members, 25) as $batch) {
            $reports = $this->fetchHfmReportsBatch($batch);
            foreach ($batch as $member) {
            $report = $reports[(string) $member['id_hfm']] ?? null;
            if (!$report || empty($report['id'])) { $failed++; continue; }
            $data = ['last_trade' => $this->normaliseDate($report['last_trade'] ?? null)];
            if (!empty($report['name'])) { $data['nama'] = $report['name']; }
            if (!empty($report['account_currency'])) { $data['currency'] = $report['account_currency']; }
            $db->table('tb_member_vip')->where('id', $member['id'])->update($data);
            $updated++;
            }
        }
        return ['total' => count($members), 'updated' => $updated, 'failed' => $failed];
    }

    private function fetchHfmReportsBatch(array $members): array
    {
        $multi = curl_multi_init();
        $handles = [];
        foreach ($members as $member) {
            $id = (string) $member['id_hfm'];
            if ($id === '') continue;
            $ch = curl_init(self::HFM_BASE_URL . rawurlencode($id) . '/report');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . self::HFM_API_KEY, 'Accept: application/json'],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT => 10,
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$id] = $ch;
        }
        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) curl_multi_select($multi, 1.0);
        } while ($running && $status === CURLM_OK);
        $reports = [];
        foreach ($handles as $id => $ch) {
            $decoded = json_decode((string) curl_multi_getcontent($ch), true);
            if (isset($decoded[0]) && is_array($decoded[0])) $decoded = $decoded[0];
            elseif (isset($decoded['data']) && is_array($decoded['data'])) $decoded = $decoded['data'];
            if (is_array($decoded)) $reports[$id] = $decoded;
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }
        curl_multi_close($multi);
        return $reports;
    }

    private function phaseByKey(string $key): ?array
    {
        foreach ($this->phases() as $phase) if ($phase['phase_key'] === $key) return $phase;
        return null;
    }

    private function addHfmData(array $member): ?array
    {
        $report = $this->fetchHfmReport((string) $member['id_hfm']);
        if ($report === null || empty($report['id'])) {
            return null;
        }
        $member['api_last_trade'] = $this->normaliseDate($report['last_trade'] ?? null);
        $member['last_trade_display'] = $member['api_last_trade']
            ? date('d M Y H:i', strtotime($member['api_last_trade']))
            : 'Belum Trading';
        $member['api_checked_at'] = date('Y-m-d H:i:s');
        $member['api_name'] = $report['name'] ?? null;
        return $member;
    }

    private function fetchHfmReport(string $idHfm): ?array
    {
        if ($idHfm === '') {
            return null;
        }
        $ch = curl_init(self::HFM_BASE_URL . rawurlencode($idHfm) . '/report');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . self::HFM_API_KEY, 'Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        if (!$raw) {
            return null;
        }
        $decoded = json_decode($raw, true);
        if (isset($decoded[0]) && is_array($decoded[0])) {
            return $decoded[0];
        }
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            return $decoded['data'];
        }
        return is_array($decoded) ? $decoded : null;
    }

    private function normaliseDate($value): ?string
    {
        if (!is_string($value) || !preg_match('/^20\d{2}-\d{2}-\d{2}/', $value)) {
            return null;
        }
        $timestamp = strtotime($value);
        return $timestamp === false ? null : date('Y-m-d H:i:s', $timestamp);
    }

    private function getGlobal($db, string $key): string
    {
        $row = $db->table('bot_globals')->where('key_name', $key)->get()->getRowArray();
        return trim((string) ($row['key_value'] ?? ''));
    }

    private function sendAdminReport(string $token, string $adminId, array $result, array $sentMembers): void
    {
        if ($adminId === '' || ($result['due'] === 0 && $result['failed'] === 0)) {
            return;
        }
        $lines = [
            '<b>📋 LAPORAN PENGINGAT AKTIVITAS MEMBER</b>',
            'Waktu: ' . date('d-m-Y H:i:s'),
            'Sumber status trading: API HFM',
            'Jatuh tempo: ' . $result['due'],
            'Berhasil dikirim: ' . $result['sent'],
            'Sudah pernah dikirim: ' . $result['skipped'],
            'Gagal: ' . $result['failed'],
        ];
        foreach ($sentMembers as $member) {
            $name = htmlspecialchars((string) ($member['nama'] ?: '-'), ENT_QUOTES, 'UTF-8');
            $hfm = htmlspecialchars((string) $member['id_hfm'], ENT_QUOTES, 'UTF-8');
            $telegram = htmlspecialchars((string) $member['id_telegram'], ENT_QUOTES, 'UTF-8');
            $lines[] = "✅ {$name} | HFM: {$hfm} | <a href=\"tg://user?id={$telegram}\">Telegram</a>";
        }
        foreach ($result['errors'] as $error) {
            $lines[] = '❌ ' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8');
        }
        $chunks = [];
        $current = '';
        foreach ($lines as $line) {
            if ($current !== '' && strlen($current . "\n" . $line) > 3800) {
                $chunks[] = $current;
                $current = '';
            }
            $current .= ($current === '' ? '' : "\n") . $line;
        }
        if ($current !== '') {
            $chunks[] = $current;
        }
        foreach ($chunks as $chunk) {
            $this->sendTelegram($token, $adminId, $chunk, 'HTML');
        }
    }

    private function sendTelegram(string $token, string $chatId, string $text, ?string $parseMode = null): array
    {
        if ($token === '') {
            return ['ok' => false, 'description' => 'TELEGRAM_TOKEN belum diatur di bot_globals'];
        }
        $payload = ['chat_id' => $chatId, 'text' => $text];
        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }
        $ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) {
            return ['ok' => false, 'description' => $error];
        }
        $data = json_decode((string) $raw, true);
        return is_array($data) ? $data : ['ok' => false, 'description' => 'Respons Telegram tidak valid'];
    }
}
