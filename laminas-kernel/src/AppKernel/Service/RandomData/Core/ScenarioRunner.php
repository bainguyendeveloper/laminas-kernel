<?php

namespace AppKernel\Service\RandomData\Core;

class ScenarioRunner
{
    public function run(ScenarioInterface $scenario, Context $context): Context
    {
        $context->data['start_time'] = microtime(true);

        try {
            $scenario->run($context);
            $context->data['status'] = 'success';
        } catch (\Throwable $e) {
            $context->data['status'] = 'failed';
            $context->data['error'] = $e->getMessage();
        }

        $context->data['end_time'] = microtime(true);
        $totalDurationMs = 0;

        if (isset($context->data['start_time'], $context->data['end_time'])) {
            $totalDurationMs = ($context->data['end_time'] - $context->data['start_time']) * 1000;
        }

        $context->data['total_duration_ms'] = $totalDurationMs;
        return $context;
    }
}