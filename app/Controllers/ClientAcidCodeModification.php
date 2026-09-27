<?php

namespace App\Controllers;

use Config\Database;

class ClientAcidCodeModification extends BaseController
{
    private string $apiKey = '';

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = (string) (getenv('HFM_API_KEY') ?: '');
    }
    private string $baseUrl = 'https://api.hfm-partners.com/api';

    public function index()
    {
        return view('client_acid_code_modification', [
            'title' => 'Modifikasi ACID Code Wallet',
            'members' => $this->members(),
            'oldWalletId' => (string) ($this->request->getGet('client_wallet_id') ?? ''),
        ]);
    }

    public function modify()
    {
        return $this->sendModification('wallet', 'client_wallet_id', 'client-acid-code-modification', 'Client wallet ID');
    }

    public function tradingAccount()
    {
        return view('client_acid_code_modification_trading_account', [
            'title' => 'Modifikasi ACID Code Trading Account',
            'members' => $this->members(),
            'oldTradingAccountId' => (string) ($this->request->getGet('client_trading_account_id') ?? ''),
        ]);
    }

    public function modifyTradingAccount()
    {
        return $this->sendModification(
            'trading-account',
            'client_trading_account_id',
            'client-acid-code-modification/trading-account',
            'Client trading account ID'
        );
    }

    private function members(): array
    {
        return Database::connect()->table('tb_member_vip')
            ->select('id_hfm, nama, email, status, currency')
            ->where('id_hfm IS NOT NULL', null, false)
            ->where('id_hfm !=', '')
            ->orderBy('nama', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function sendModification(string $accountType, string $idField, string $redirectPath, string $idLabel)
    {
        $accountId = trim((string) $this->request->getPost($idField));
        $acid = trim((string) $this->request->getPost('acid'));

        if (! preg_match('/^[1-9][0-9]*$/', $accountId)) {
            return redirect()->to(base_url($redirectPath))
                ->withInput()
                ->with('error', $idLabel . ' wajib berupa angka positif.');
        }

        if ($acid === '' || strlen($acid) > 100) {
            return redirect()->to(base_url($redirectPath))
                ->withInput()
                ->with('error', 'ACID wajib diisi dan maksimal 100 karakter.');
        }

        $url = $this->baseUrl . '/client-acid-code-modification/' . $accountType . '/'
            . rawurlencode($accountId) . '?' . http_build_query(['acid' => $acid]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'BOSSCUAN/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $rawResponse = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError !== '') {
            return redirect()->to(base_url($redirectPath))
                ->withInput()
                ->with('error', 'Gagal terhubung ke API HFM: ' . $curlError);
        }

        $response = json_decode((string) $rawResponse, true);
        if ($httpCode === 200) {
            $message = is_string($response)
                ? $response
                : (string) ($response['message'] ?? $response['detail'] ?? 'ACID code berhasil dimodifikasi.');

            return redirect()->to(base_url($redirectPath))
                ->with('success', $message);
        }

        $message = $this->formatApiError($response, $rawResponse, $httpCode);
        return redirect()->to(base_url($redirectPath))
            ->withInput()
            ->with('error', $message);
    }

    private function formatApiError(mixed $response, string $rawResponse, int $httpCode): string
    {
        if (is_array($response) && isset($response['detail'])) {
            if (is_array($response['detail'])) {
                $details = [];
                foreach ($response['detail'] as $detail) {
                    if (is_array($detail) && isset($detail['msg'])) {
                        $details[] = (string) $detail['msg'];
                    } elseif (is_string($detail)) {
                        $details[] = $detail;
                    }
                }
                if ($details !== []) {
                    return 'API HFM menolak permintaan (HTTP ' . $httpCode . '): ' . implode('; ', $details);
                }
            } elseif (is_string($response['detail'])) {
                return 'API HFM menolak permintaan (HTTP ' . $httpCode . '): ' . $response['detail'];
            }
        }

        if (is_array($response) && isset($response['message']) && is_string($response['message'])) {
            return 'API HFM mengembalikan HTTP ' . $httpCode . ': ' . $response['message'];
        }

        return 'API HFM mengembalikan HTTP ' . $httpCode . '. Periksa client wallet ID dan ACID code.';
    }
}
