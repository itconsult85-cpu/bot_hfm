<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class BackfillTelegramUsernames extends BaseCommand
{
    protected $group = 'Telegram';
    protected $name = 'telegram:backfill-usernames';
    protected $description = 'Mengisi username Telegram yang kosong dari ID pada riwayat chat bot.';

    public function run(array $params)
    {
        $db = Database::connect();
        if (
            !$db->tableExists('telegram_group_members')
            || !$db->fieldExists('telegram_username', 'telegram_group_members')
            || !$db->tableExists('chat_logs_tele')
        ) {
            CLI::error('Pastikan tabel telegram_group_members, kolom telegram_username, dan tabel chat_logs_tele sudah tersedia.');
            return EXIT_ERROR;
        }

        CLI::write('Mencari ID dengan username kosong yang juga tercatat di chat_logs_tele...');
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
        $rows = $db->query(<<<'SQL'
SELECT DISTINCT TRIM(members.telegram_id) AS telegram_id
FROM telegram_group_members AS members
WHERE (members.telegram_username IS NULL OR TRIM(members.telegram_username) = '')
  AND TRIM(members.telegram_id) REGEXP '^[1-9][0-9]*$'
  AND EXISTS (
      SELECT 1
      FROM chat_logs_tele AS logs
      WHERE logs.sender = 'user'
        AND BINARY TRIM(logs.phone_number) = BINARY TRIM(members.telegram_id)
  )
ORDER BY TRIM(members.telegram_id)
SQL)->getResultArray();

        $telegramIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row): string => trim((string) ($row['telegram_id'] ?? '')),
            $rows
        ), static fn (string $id): bool => preg_match('/^[1-9][0-9]*$/', $id) === 1)));

        if ($telegramIds === []) {
            CLI::write('Tidak ada username kosong yang cocok dengan ID pada chat_logs_tele.');
            return EXIT_SUCCESS;
        }
        CLI::write('Kandidat ditemukan: ' . count($telegramIds) . ' ID. Memulai lookup Telegram.');
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();

        $tokenRow = $db->table('bot_globals')->select('key_value')
            ->where('key_name', 'BOT_CONTROL_TOKEN')
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();
        $token = trim((string) ($tokenRow['key_value'] ?? ''));
        if ($token === '') {
            CLI::error('BOT_CONTROL_TOKEN belum dikonfigurasi.');
            return EXIT_ERROR;
        }
        if (!function_exists('curl_init')) {
            CLI::error('Ekstensi cURL PHP belum tersedia.');
            return EXIT_ERROR;
        }

        $host = trim((string) (getenv('BOT_API_HOST') ?: '127.0.0.1'));
        $port = (int) (getenv('API_PORT') ?: 3000);
        if ($port < 1 || $port > 65535) {
            $port = 3000;
        }
        $initialCandidateCount = count($telegramIds);
        $candidateSet = array_fill_keys($telegramIds, true);
        $rosterResolved = [];
        CLI::write('Mencoba mencocokkan kandidat dengan roster grup aktif melalui MTProto...');
        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
        $scanCh = curl_init(sprintf('http://%s:%d/api/mtproto-group-members', $host, $port));
        curl_setopt_array($scanCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => ['X-Bot-Control-Token: ' . $token],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 180,
        ]);
        $scanRaw = curl_exec($scanCh);
        $scanError = curl_error($scanCh);
        $scanCode = (int) curl_getinfo($scanCh, CURLINFO_HTTP_CODE);
        curl_close($scanCh);
        $scanResponse = json_decode($scanRaw ?: '', true);
        if (
            !$scanError
            && $scanCode >= 200
            && $scanCode < 300
            && ($scanResponse['status'] ?? '') === 'success'
            && is_array($scanResponse['members'] ?? null)
        ) {
            foreach ($scanResponse['members'] as $member) {
                $telegramId = trim((string) ($member['telegram_id'] ?? ''));
                $username = ltrim(trim((string) ($member['username'] ?? '')), '@');
                if (
                    isset($candidateSet[$telegramId])
                    && preg_match('/^[A-Za-z][A-Za-z0-9_]{4,31}$/', $username) === 1
                ) {
                    $db->query(
                        'UPDATE telegram_group_members SET telegram_username = ? WHERE telegram_id = ?',
                        [$username, $telegramId],
                    );
                    $rosterResolved[$telegramId] = true;
                }
            }
            CLI::write('Username kandidat ditemukan dari roster grup aktif: ' . count($rosterResolved) . '.');
        } else {
            CLI::write('Roster grup tidak dapat dipindai; lanjutkan lookup per ID.');
        }
        if ($rosterResolved !== []) {
            $telegramIds = array_values(array_diff($telegramIds, array_keys($rosterResolved)));
        }
        $remainingLookupCount = count($telegramIds);

        $url = sprintf('http://%s:%d/api/resolve-telegram-usernames', $host, $port);
        $resolvedCount = count($rosterResolved);
        $failedBatches = 0;
        $diagnosticKeys = [
            'found_in_chat_logs',
            'not_found_in_chat_logs',
            'bot_api_chat_returned',
            'bot_api_error',
            'bot_api_username_missing',
            'mtproto_attempted',
            'mtproto_entity_returned',
            'mtproto_error',
            'mtproto_username_missing',
        ];
        $diagnostics = array_fill_keys($diagnosticKeys, 0);
        $diagnosticsAvailable = false;
        $batches = array_chunk($telegramIds, 25);

        foreach ($batches as $batchIndex => $batch) {
            CLI::write(sprintf('Batch %d/%d: memproses %d ID...', $batchIndex + 1, count($batches), count($batch)));
            if (ob_get_level() > 0) {
                @ob_flush();
            }
            flush();
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Bot-Control-Token: ' . $token,
                ],
                CURLOPT_POSTFIELDS => json_encode(['telegram_ids' => $batch]),
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 180,
            ]);
            $raw = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $response = json_decode($raw ?: '', true);
            if (
                $curlError
                || $httpCode < 200
                || $httpCode >= 300
                || ($response['status'] ?? '') !== 'success'
                || !is_array($response['usernames'] ?? null)
            ) {
                $failedBatches++;
                CLI::error($curlError ?: (string) ($response['error'] ?? 'Bot gagal memproses batch username.'));
                continue;
            }

            $foundInBatch = count($response['usernames']);
            $resolvedCount += $foundInBatch;
            if (is_array($response['diagnostics'] ?? null)) {
                $diagnosticsAvailable = true;
                foreach ($diagnosticKeys as $key) {
                    $diagnostics[$key] += max(0, (int) ($response['diagnostics'][$key] ?? 0));
                }
            }
            CLI::write('Batch selesai; username ditemukan: ' . $foundInBatch . '.');
        }

        $unresolvedCount = max(0, $initialCandidateCount - $resolvedCount);
        CLI::write('ID diperiksa: ' . $initialCandidateCount);
        CLI::write('Username ditemukan dan disimpan: ' . $resolvedCount);
        CLI::write('Belum dapat diresolusikan: ' . $unresolvedCount);
        CLI::write('Lookup mencoba Bot API lalu MTProto. Tidak ada pesan yang dikirim ke pengguna.');
        if ($diagnosticsAvailable) {
            CLI::write('Diagnostik agregat (tanpa data pengguna):');
            CLI::write('  IDs cocok di chat_logs_tele: ' . $diagnostics['found_in_chat_logs']);
            CLI::write('  Bot API chat berhasil dikembalikan: ' . $diagnostics['bot_api_chat_returned']);
            CLI::write('  Bot API error: ' . $diagnostics['bot_api_error']);
            CLI::write('  Bot API tanpa username publik: ' . $diagnostics['bot_api_username_missing']);
            CLI::write('  Percobaan MTProto: ' . $diagnostics['mtproto_attempted']);
            CLI::write('  MTProto entity berhasil ditemukan: ' . $diagnostics['mtproto_entity_returned']);
            CLI::write('  MTProto error/tidak dapat resolve: ' . $diagnostics['mtproto_error']);
            CLI::write('  MTProto entity tanpa username publik: ' . $diagnostics['mtproto_username_missing']);
        } elseif ($remainingLookupCount > 0) {
            CLI::write('Bot belum mengirim statistik diagnostik; pastikan bot.js terbaru sudah dipasang dan direstart.');
        }

        return $failedBatches > 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
