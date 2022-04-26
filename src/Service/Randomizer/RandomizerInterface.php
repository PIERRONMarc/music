<?php

namespace App\Service\Randomizer;

interface RandomizerInterface
{
    /**
     * Generate a random value via the Mersenne Twister Random Number Generator.
     *
     * @param int $min lowest value to be returned
     * @param int $max highest value to be returned
     *
     * @return int A random integer value between min (or 0) and max (or mt_getrandmax(), inclusive), or false if max is less than min
     */
    public function mtRand(int $min, int $max): int;
}
