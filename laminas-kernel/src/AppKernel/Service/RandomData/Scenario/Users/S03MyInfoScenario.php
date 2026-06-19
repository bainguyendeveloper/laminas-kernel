<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace AppKernel\Service\RandomData\Scenario\Users;

use AppKernel\Service\RandomData\Core\AbstractScenario;
use AppKernel\Service\RandomData\Core\Context;

class S03MyInfoScenario extends AbstractScenario {

    private $endpoint;
    
    public function setEndpoint(string $endpoint) {
        $this->endpoint = $endpoint;
        return $this;
    }


    public function run(Context $context): void {
        
        $start_time = microtime(true);
        $accessToken = $context->data['user']['accessToken']??$this->faker->faker()->sha256;
        $payload = [
            
        ];
        try {
            $res = $this->client->post($this->endpoint,$payload,$accessToken);
            $context->data['steps'][$this->endpoint] = ['success' => true, 'laststepsuccess' => true, 'message' => $res['message'] ??'' ];
            $context->data['user'] =  $res['item']??[];
        } catch (\Throwable $e) {
            $context->data['steps'][$this->endpoint] = ['success' => false, 'laststepsuccess' => false, 'message' => $e->getMessage()];
            $res = $e->getMessage();
        }
        $this->log($context, 'Users', $this->endpoint, [
            'success' => $context->data['steps'][$this->endpoint]['success'],
            'message' => $context->data['steps'][$this->endpoint]['message'],
            'method' => 'post',
            'payload' => $payload,
            'token'=>$accessToken,
            'response' => $res,
            'start_time' => $start_time
        ]);
    }
}
