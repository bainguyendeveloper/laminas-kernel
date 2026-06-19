<?php

namespace AppKernel\Service\RandomData\Core;
interface ScenarioInterface
{
     public function run(Context $context): void;
}