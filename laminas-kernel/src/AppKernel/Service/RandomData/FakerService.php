<?php

namespace AppKernel\Service;

use Faker\Generator;

class FakerService
{
    public function __construct(private Generator $faker)
    {
    }

    public function user(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];
    }
    public function tenant(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
        ];
    }
}