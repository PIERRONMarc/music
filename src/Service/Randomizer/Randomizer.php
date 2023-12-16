<?php

namespace App\Service\Randomizer;

/**
 * This class exist to control random output for testing purpose.
 */
class Randomizer implements RandomizerInterface
{
    public function mtRand(int $min, int $max): int
    {
        return mt_rand($min, $max);
    }
}
