<?php

namespace App\Controllers;

class HfmDemo extends BaseController
{
    private string $baseUrl = 'https://api.hfm-partners.com/api';

    public function index()
    {
        return view('hfm_demo_client', [
            'title' => 'HFM Demo Client',
            'result' => null,
            'walletId' => '',
        ]);
    }

    public function check()
    {
        $walletId = trim((string) $this->request->getPost('client_wallet_id'));
        $result = null;

        if (! preg_match('/^[1-9][0-9]*$/', $walletId)) {
            $result = [
                'ok' => false,
                'message' => 'Client wallet ID wajib berupa angka positif.',
            ];
        } else {
            $result = $this->requestDemoClient($walletId);
        }

        return view('hfm_demo_client', [
            'title' => 'HFM Demo Client',
            'result' => $result,
            'walletId' => $walletId,
        ]);
    }

    private function requestDemoClient(string $walletId): array
    {
        $apiKey = trim((string) (getenv('HFM_API_KEY') ?: ''));
        if ($apiKey === '') {
            return [
                'ok' => false,
                'message' => 'HFM_API_KEY belum dikonfigurasi di environment.',
            ];
        }

        $url = $this->baseUrl . '/clients/' . rawurlencode($walletId) . '/demo';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
        ]);

        $raw = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError !== '') {
            return [
                'ok' => false,
                'http_code' => $httpCode,
                'message' => 'Tidak dapat terhubung ke API HFM: ' . $curlError,
            ];
        }

        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return [
                'ok' => false,
                'http_code' => $httpCode,
                'message' => 'API HFM mengembalikan response yang bukan JSON valid.',
                'raw' => (string) $raw,
            ];
        }

        return [
            'ok' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'response' => $decoded,
            'raw' => (string) $raw,
            'message' => $httpCode >= 200 && $httpCode < 300
                ? 'Request ke endpoint HFM berhasil.'
                : 'API HFM mengembalikan error HTTP ' . $httpCode . '.',
        ];
    }
}
