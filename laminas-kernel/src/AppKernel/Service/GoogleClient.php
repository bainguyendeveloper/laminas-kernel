<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace AppKernel\Service;

/**
 * Description of GoogleClient
 *
 * @author bainguyen
 */
class GoogleClient {

//    private $client;
    protected $em;
    protected $translator;
    protected $config;
    protected $container;

    public function __construct($em, $translator, $config, $container) {
        $this->em = $em;
        $this->translator = $translator;
        $this->config = $config;
        $this->container = $container;
    }

    public function getAuthUrl() {
        if (($this->client) && method_exists($this->client, 'createAuthUrl')):
            return $this->client->createAuthUrl();
        else:
            throw new \Exception('Chưa cấu hình google client');
        endif;
    }

    public function getInfoByCode(string $code = '', $redirectUri = '') {
         if (!$code):
             throw new \Exception('Code không hợp lệ');
         endif;
             
        $clientId = $this->config['settings']['social']['clientId'] ?? '';
        $clientSecret = $this->config['settings']['social']['clientSecret'] ?? '';
//        $redirectUri = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/login-with-social';
        if (!$redirectUri):
            $redirectUri = 'http://localhost:3000/api/auth/callback/google';
        endif;

        if ($clientId && $clientSecret):
            $client = new \Google_Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri($redirectUri);
            $client->addScope("email");
            $client->addScope("profile");
            $client->setRedirectUri($redirectUri);
          


                $client->authenticate($code);
                $client->getAccessToken();
//                $this->client->setAccessToken($token['access_token']);
                // 2. Lấy thông tin profile từ Google
                $google_oauth = new \Google_Service_Oauth2($client);
                $google_account_info = $google_oauth->userinfo->get();
                return [
                    'id' => $google_account_info->id,
                    'picture' => $google_account_info->picture,
                    'email' => $google_account_info->email,
                    'name' => $google_account_info->name,
                ];
        else:
            throw new \Exception('Chưa cấu hình google client');
        endif;
    }
}
