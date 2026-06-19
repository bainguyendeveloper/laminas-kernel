<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace AppKernel\Service\RandomData\Scenario\Users;

use AppKernel\Service\RandomData\Core\AbstractScenario;
use AppKernel\Service\RandomData\Core\Context;

class S02LoginScenario extends AbstractScenario {

    private $endpoint;
    
    public function setEndpoint(string $endpoint) {
        $this->endpoint = $endpoint;
        return $this;
    }


    public function run(Context $context): void {
        
        $start_time = microtime(true);
        $password = $context->data['user']['password']??$this->faker->faker()->password;
        $username = $context->data['user']['username']??$this->faker->faker()->email;
        $payload = [
            'password'=>$password,
            'username'=>$username,
        ];
        try {
            $res = $this->client->get($this->endpoint,$payload);
            $context->data['steps'][$this->endpoint] = ['success' => true, 'laststepsuccess' => true, 'message' => $res['message'] ??''  ];
        } catch (\Throwable $e) {
            $context->data['steps'][$this->endpoint] = ['success' => false, 'laststepsuccess' => false, 'message' => $e->getMessage()];
            $res = $e->getMessage();
        }
        $this->log($context, 'Users', $this->endpoint, [
            'success' => $context->data['steps'][$this->endpoint]['success'],
            'message' => $context->data['steps'][$this->endpoint]['message'],
            'method' => 'post',
            'payload' => $payload,
            'response' => $res,
            'start_time' => $start_time
        ]);
    }
}
