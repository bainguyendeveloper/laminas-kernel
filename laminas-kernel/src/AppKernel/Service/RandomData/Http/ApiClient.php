<?php

namespace AppKernel\Service\RandomData\Http;

use GuzzleHttp\Client;

class ApiClient {

    public function __construct(private Client $client) {
        
    }

    public function get($url, $query = [], $token = null) {
        $res = $this->client->get($url, [
            'query' => $query,
            'headers' => $token ? [
         'Authorization' => "Bearer {$token}",
            ] : []
        ]);
        return $this->embedData($res);
    }

    public function post($url, $data, $token = null) {
        $res = $this->client->post($url, [
            'json' => $data,
            'headers' => $token ? [
        'Authorization' => "Bearer {$token}",
            ] : []
        ]);
        return $this->embedData($res);
    }

    public function put($url, $data, $token = null) {
        $res = $this->client->put($url, [
            'json' => $data,
            'headers' => $token ? [
        'Authorization' => "Bearer {$token}",
            ] : []
        ]);
        return $this->embedData($res);
    }

    public function patch($url, $data, $token = null) {
        $res = $this->client->patch($url, [
            'json' => $data,
            'headers' => $token ? [
         'Authorization' => "Bearer {$token}",
            ] : []
        ]);

        return $this->embedData($res);
    }

    public function embedData($res) {
        $content = $res->getBody()->getContents();
       if($content):
           try {
               $body = json_decode($res->getBody(), true);
           } catch (Exception $exc) {
               echo '<pre>';
               var_dump($content);
               echo '</pre>';
               exit;
           }


       endif;
       return $body;
    }
}
