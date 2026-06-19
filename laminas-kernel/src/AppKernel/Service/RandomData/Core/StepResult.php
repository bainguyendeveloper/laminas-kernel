<?php
namespace AppKernel\Service\RandomData\Core;

class StepResult
{
    public function __construct(
        public string $stepName,
        public bool $success = true,
        public mixed $request = null,
        public mixed $response = null,
        public ?string $error = null,
        public float $durationMs = 0,
        public array $meta = []
    ) {}

    public static function success(
        string $stepName,
        mixed $request,
        mixed $response,
        float $durationMs
    ): self {
        return new self(
            stepName: $stepName,
            success: true,
            request: $request,
            response: $response,
            durationMs: $durationMs
        );
    }

    public static function fail(
        string $stepName,
        mixed $request,
        string $error,
        float $durationMs
    ): self {
        return new self(
            stepName: $stepName,
            success: false,
            request: $request,
            response: null,
            error: $error,
            durationMs: $durationMs
        );
    }
}