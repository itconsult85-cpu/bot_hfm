<?php

namespace App\Libraries;

/** Retrieves a fresh published USD/IDR reference rate for each validation. */
class ExchangeRateService
{
    /**
     * Fetch the latest rate directly. If the API is unavailable, return null;
     * do not silently reuse an older observation.
     *
     * @return array{rate: float, date: string, fetched_at: int}|null
     */
    public function getUsdIdrRate(): ?array
    {
        return $this->fetchLatestRate();
    }

    /**
     * @return array{rate: float, date: string, fetched_at: int}|null
     */
    private function fetchLatestRate(): ?array
    {
        $ch = curl_init('https://api.frankfurter.dev/v2/rate/usd/idr');
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'HFM-Admin-ExchangeRate/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($response) || $response === '' || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        $rate = is_array($data) ? (float) ($data['rate'] ?? 0) : 0;
        $date = is_array($data) ? (string) ($data['date'] ?? '') : '';

        if ($rate < 1000 || $rate > 100000 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        return [
            'rate' => $rate,
            'date' => $date,
            'fetched_at' => time(),
        ];
    }
}
