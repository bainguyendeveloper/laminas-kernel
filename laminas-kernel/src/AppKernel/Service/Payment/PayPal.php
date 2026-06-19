<?php

namespace AppKernel\Service\Payment;

/**
 * Description of PayPal
 *
 * @author bainguyen
 */
class PayPal {

    protected $client;
    protected string $clientId;
    protected string $secret;
    protected string $baseUrl;

    public function __construct(string $clientId, string $secret, string $baseUrl) {
        $this->clientId = $clientId;
        $this->secret = $secret;

        $this->baseUrl = $baseUrl;

        $this->client = new \GuzzleHttp\Client();
    }

    public function getAccessToken(): string {
        $response = $this->client->post(
                $this->baseUrl . '/v1/oauth2/token',
                [
                    'auth' => [
                        $this->clientId,
                        $this->secret
                    ],
                    'form_params' => [
                        'grant_type' => 'client_credentials'
                    ]
                ]
        );

        $data = json_decode(
                $response->getBody()->getContents(),
                true
        );

        return $data['access_token'];
    }

    public function getRate() {
        $response = file_get_contents(
                'https://api.frankfurter.dev/v2/rate/USD/VND'
        );
        $data = ($response) ? json_decode($response, true) : [];
        return (int) ($data['rate'] ?? 0);
    }

    public function createUrl($params = []): array {
        $rate = $this->getRate();
        if(!$rate):
            return ['error' => 'Không thể lấy tỉ giá', 'code' => 403];
        endif;
        $requestId = trim($params['requestId'] ?? '');
        $vndAmount = (float) ($params['amount'] ?? 0);
        $amount = round($vndAmount/$rate, 2);
        $currency = 'USD';
        $returnUrl = trim($params['returnUrl'] ?? '');
        $cancelUrl = trim($params['cancelUrl'] ?? '');

        $payload = [
            'intent' => 'CAPTURE',
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'landing_page' => 'LOGIN',
                        'shipping_preference' => 'GET_FROM_FILE',
                        'user_action' => 'PAY_NOW',
                        'return_url' => $returnUrl,
                        'cancel_url' => $cancelUrl
                    ]
                ]
            ],
            'purchase_units' => [
                [
                    'invoice_id' => $requestId,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $amount,
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => $currency,
                                'value' => $amount
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $items = ($params['items'] ?? []);
        if ($items):
            $payload['purchase_units']['items'] = $items;
//                'items' => [
//                        [
//                            'name' => 'T-Shirt',
//                            'description' => 'Super Fresh Shirt',
//                            'unit_amount' =>
//                            [
//                                'currency_code' => $currency,
//                                'value' => $amount,
//                            ],
//                            'quantity' => 1,
//                            'sku' => 'sku01'
//                        ]
//                    ]
        endif;
        $token = $this->getAccessToken();
        try {
            $response = $this->client->post(
                    $this->baseUrl . '/v2/checkout/orders',
                    [
                        'headers' => [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $token,
                            'PayPal-Request-Id' => $requestId,
                        ],
                        'json' => $payload
                    ]
            );
        } catch (\GuzzleHttp\Exception\ClientException $exc) {
            return ['error' => $exc->getMessage(), 'code' => $exc->getCode()];
        }


        $data = json_decode(
                $response->getBody()->getContents(),
                true
        );
        $approveUrl = null;

        foreach ($data['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approveUrl = $link['href'];
                break;
            }
        }

        return $data['links'][1] ?? '';
    }

    public function captureToken(string $orderId): array {
        $token = $this->getAccessToken();

        try {
            $response = $this->client->post(
                    $this->baseUrl . '/v2/checkout/orders/' . $orderId . '/capture',
                    [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $token,
                            'Content-Type' => 'application/json'
                        ]
                    ]
            );
        } catch (\GuzzleHttp\Exception\ClientException $exc) {
            return ['error' => $exc->getMessage(), 'code' => $exc->getCode()];
        }



        return json_decode(
                $response->getBody()->getContents(),
                true
        );
    }
}
