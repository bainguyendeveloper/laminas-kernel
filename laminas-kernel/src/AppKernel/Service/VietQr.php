<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

namespace AppKernel\Service;

class VietQr {

    public $clientId = '919a17ca-dd0f-44e4-b1b4-eb4332cd817a';
    public $apiKey = 'a21777c4-5bef-43b3-92a8-66f26600bd0b';
    public $accountNo = '19028924538018';
    public $accountName = 'NGUYEN VAN BAI';
    public $addInfo = 'test chuyen khoan';
    public $acqId = '970407';

    function generateVietQR(
            float $amount = 20000,
            string $accountNo = '',
            string $accountName = '',
            string $acqId = '',
            string $addInfo = '',
            string $template = 'qr_only',
    ): array {
        $payload = json_encode([
            'accountNo' => $accountNo ?: $this->accountNo,
            'accountName' => $accountName ?: $this->accountName,
            'acqId' => $acqId ?: $this->acqId,
            'addInfo' => $addInfo ?: $this->addInfo,
            'amount' => $amount,
            'template' => $template,
        ]);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.vietqr.io/v2/generate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'x-client-id: ' . $this->clientId,
                'x-api-key: ' . $this->apiKey,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new \RuntimeException('API error ' . $httpCode . ': ' . ($data['desc'] ?? 'Unknown error'));
        }

        return $data;
    }

    function getVietQRBanks(): array {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.vietqr.io/v2/banks',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            throw new \RuntimeException('API error ' . $httpCode . ': ' . ($data['desc'] ?? 'Unknown error'));
        }

        return $data['data'];
    }
}
