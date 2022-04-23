<?php

namespace App\Service\RandomNameGenerator;

interface RandomNameGeneratorInterface
{
    /**
     * Get a randomly generated name.
     */
    public function getName(): string;
}
