<?php

namespace AppKernel\Service\RandomData\Support;

use Faker\Factory;
use Faker\Generator;

class FakerService {

    private Generator $faker;

    public function __construct() {
        $this->faker = Factory::create('vi_VN');
    }

    public function seed(int $seed): void {
        $this->faker->seed($seed);
    }

    public function faker(): Generator {
        return $this->faker;
    }
}
