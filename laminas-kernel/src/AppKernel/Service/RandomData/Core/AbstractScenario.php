<?php

namespace AppKernel\Service\RandomData\Core;

abstract class AbstractScenario implements ScenarioInterface {

    public function __construct(
            protected \AppKernel\Service\RandomData\Support\FakerService $faker,
            protected \AppKernel\Service\RandomData\Http\ApiClient $client,
    ) {
        
    }

    protected function log(Context $context,string $key, string $step, $data): void {
        $endtime = microtime(true);
        $_logdata = [
            'step' => $step,
            'data' => $data,
            'start_time' => null,
            'during' => null,
            'end_time' => $endtime,
        ];
        if ($data['start_time'] ?? 0):
            $_logdata['start_time'] = $data['start_time'];
            $_logdata['during'] = $endtime - $data['start_time'];
             unset($data['start_time']);
        endif;
        if(!isset($context->logs[$key])):
            $context->logs[$key] =[];
        endif;
        $context->logs[$key][] = $_logdata;
    }
}
