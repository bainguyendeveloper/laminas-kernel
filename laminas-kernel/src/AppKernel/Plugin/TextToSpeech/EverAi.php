<?php

namespace AppKernel\Plugin\TextToSpeech;

class EverAi {

    public $apiKey;
    public $host;
    public $voiceCode;
    public $config;
    public $logger;

    function __construct($config = []) {
        $this->apiKey = 'GtPStPYyEgH5zJWpyfpSOGG4vE1ekohrS';
        $this->host = 'https://www.everai.vn/api/v1/tts';
        $voiceCodes = [
            'vi_male_lehoang_mb', //Lê Hoàng - Nam - Miền Bắc
            'vi_female_thuytrang_mb', //Thùy Trang - Nữ - Miền Bắc
            'vi_male_minhtriet_mb', //Minh Triết -Nam - Miền Bắc
            'vi_male_echo_default', // Echo Nam - Giọng Mỹ
            'vi_female_nova_default', // Nova Nữ - Giọng Mỹ
            'vi_male_onyx_default', // Onyx Nam - Giọng Mỹ
            'vi_female_hacuc_mb', // Hạ Cúc - Nữ - Miền Bắc
            'vi_male_ductrong_mb', // Đức Trọng - Nam - Miền Bắc
            'vi_female_kieunhi_mn', // Kiều Nhi - Nữ - Miền Nam
            'vi_female_huyenanh_mb', //Huyền Anh - Nữ - Miền Bắc
            'vi_female_halinh_mb', //Hà Linh Nữ - Miền Bắc
            'vi_female_hoaian_mb', //Hoài An - Nữ - Miền Bắc
            'vi_female_khanhhuyentvc_mb'//Khánh Huyền  - Nữ - Miền Bắc
        ];
        $this->voiceCode = 'vi_female_kieunhi_mn';
        $this->host = 'https://www.everai.vn/api/v1/';
        $this->config = $config;
        $this->logger = new \Monolog\Logger('frontend');
        $stream = new \Monolog\Handler\StreamHandler(PATH_DATA . '/logs/' . date('Y-m-d') .'_' . __NAMESPACE__ . '.log', \Monolog\Logger::DEBUG);
        $formatter = new \Monolog\Formatter\LineFormatter(null, null, true, true); // 3rd param true = allowInlineLineBreaks
        $stream->setFormatter($formatter);
        $this->logger->pushHandler($stream);
    }

    public function textToSpeech($text = '') {
        $callback = sprintf('%s://%s', $_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'] . '/ever-callback');
        $_fields = [
            'input_text' => $text,
            'voice_code' => $this->voiceCode,
            'voice_id'=>$this->voiceCode,
            'callback_url' => $callback,
        ];
        $this->logger->info(json_encode($_fields));
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->host . 'tts',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($_fields),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json;charset=UTF-8',
                'Authorization: Bearer ' . $this->apiKey,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $this->logger->info(json_encode($response));
    }
}
