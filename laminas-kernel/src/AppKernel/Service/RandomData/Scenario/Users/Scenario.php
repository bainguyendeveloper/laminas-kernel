<?php

namespace AppKernel\Service\RandomData\Scenario\Users;

use AppKernel\Service\RandomData\Core\AbstractScenario;
use AppKernel\Service\RandomData\Core\Context;

class Scenario extends AbstractScenario {
    protected $functions=[];

    public function __construct(
            protected \AppKernel\Service\RandomData\Support\FakerService $faker,
            protected \AppKernel\Service\RandomData\Http\ApiClient $client,
            $functions = []
    ) {
        $this->functions = $functions;
        return parent::__construct($faker, $client);
    }
    
    #[\Override]
    public function run(Context $context): void {
      $seed = $context->data['seed'] ?? 1234;
        $this->faker->seed($seed);
     foreach ($this->functions as $function):
            $class= __NAMESPACE__.'\\'.$function['class'];
            $scenario = new $class($this->faker,$this->client);
            $scenario->setEndpoint($function['endpoint']);
            $scenario->run($context);
        endforeach;
    }
}
