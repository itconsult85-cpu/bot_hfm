<?php

namespace App\Libraries;

use Config\Database;
use DateTime;
use DateTimeZone;

class ActivityReminderService
{
    private const MESSAGES = [
        'h3' => "📢 REMINDER AKTIVITAS MEMBER\n\nHalo teman-teman BO$$CUAN 👋\n\nKami ingin mengingatkan bahwa status keanggotaan di grup diperuntukkan bagi member yang aktif melakukan transaksi dan mengikuti aktivitas komunitas.\n\nBagi yang beberapa waktu terakhir belum melakukan transaksi, mohon untuk mulai kembali aktif. 🙏\n\n⏰ 3 hari ke depan akan dilakukan evaluasi aktivitas member.\n\nBagi member yang tetap tidak melakukan transaksi, akan kami hubungi kembali sebelum dilakukan penertiban grup.\n\nTerima kasih atas pengertiannya.\n🔥 Aktif bersama, cuan bersama!\n\nBO$$CUAN",
        'h1' => "⚠️ FINAL REMINDER MEMBER\n\nHalo member BO$$CUAN 👋\n\nKami mengingatkan kembali bahwa besok akan dilakukan evaluasi aktivitas member.\n\nBagi member yang belum melakukan transaksi/aktivitas, mohon segera kembali aktif agar status keanggotaan tetap dipertahankan.\n\n❗️ Member yang sampai batas waktu evaluasi belum melakukan transaksi akan masuk daftar penertiban dan dapat dikeluarkan dari grup.\n\nJika memang sedang memiliki kendala, silakan hubungi admin.\n\nTerima kasih atas perhatian dan kerja samanya. 🙏\n\n🔥 Jangan sampai kehilangan akses ke komunitas BO$$CUAN.",
        'final' => "🚨 PEMBERITAHUAN TERAKHIR\n\nHalo teman-teman 👋\n\nHari ini kami melakukan evaluasi dan penertiban member berdasarkan aktivitas transaksi di komunitas.\n\nBagi member yang sampai batas waktu yang telah ditentukan belum melakukan transaksi/aktivitas, sesuai ketentuan akan dilakukan pengeluaran dari grup.\n\nMohon dipahami bahwa langkah ini dilakukan untuk menjaga grup tetap aktif dan diisi oleh member yang benar-benar mengikuti aktivitas BO$$CUAN.\n\n🙏 Terima kasih atas kebersamaan dan pengertiannya.\n\n🔥 BO$$CUAN — Aktif, Disiplin, Cuan Bersama!",
    ];

    public function eligibleMembers(?string $phase = null): array
    {
        $db = Database::connect();
        $members = $db->table('tb_member_vip')
            ->where('status', 'aktif')
            ->where('id_telegram IS NOT NULL', null, false)
            ->where('id_telegram !=', '')
            ->where('id_telegram !=', '-')
            ->groupStart()
                ->where('last_trade IS NULL', null, false)
                ->orWhere('last_trade <= created_at', null, false)
            ->groupEnd()
            ->orderBy('created_at', 'ASC')
            ->get()->getResultArray();

        $today = new DateTime('today', new DateTimeZone('Asia/Jakarta'));
        $result = [];
        foreach ($members as $member) {
            if (empty($member['created_at'])) {
                continue;
            }
            $joined = new DateTime(substr($member['created_at'], 0, 10), new DateTimeZone('Asia/Jakarta'));
            $days = (int) $joined->diff($today)->format('%r%a');
            $memberPhase = match ($days) {
                7 => 'h3',
                9 => 'h1',
                10 => 'final',
                default => null,
            };
            if ($phase !== null && $memberPhase !== $phase) {
                continue;
            }
            if ($memberPhase !== null) {
                $member['reminder_phase'] = $memberPhase;
                $member['days_since_join'] = $days;
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
        $sent = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

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
            $response = $this->sendTelegram($token, $member['id_telegram'], self::MESSAGES[$memberPhase]);
            if ($response['ok']) {
                $db->table('activity_reminder_logs')->insert([
                    'member_id' => $member['id'],
                    'phase' => $memberPhase,
                    'telegram_id' => (string) $member['id_telegram'],
                    'sent_at' => date('Y-m-d H:i:s'),
                ]);
                $sent++;
            } else {
                $failed++;
                $errors[] = ($member['nama'] ?: $member['id_hfm']) . ': ' . ($response['description'] ?? 'Telegram gagal');
            }
        }
        return ['due' => count($due), 'sent' => $sent, 'skipped' => $skipped, 'failed' => $failed, 'errors' => $errors];
    }

    public function message(string $phase): string
    {
        return self::MESSAGES[$phase] ?? '';
    }

    private function getGlobal($db, string $key): string
    {
        $row = $db->table('bot_globals')->where('key_name', $key)->get()->getRowArray();
        return trim((string) ($row['key_value'] ?? ''));
    }

    private function sendTelegram(string $token, string $chatId, string $text): array
    {
        if ($token === '') {
            return ['ok' => false, 'description' => 'TELEGRAM_TOKEN belum diatur di bot_globals'];
        }
        $ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['chat_id' => $chatId, 'text' => $text]),
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
