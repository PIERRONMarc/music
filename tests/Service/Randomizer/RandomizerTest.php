<?php

namespace App\Tests\Service\Randomizer;

use App\Service\Randomizer\Randomizer;
use PHPUnit\Framework\TestCase;

class RandomizerTest extends TestCase
{
    public function testRandIsSuccessful(): void
    {
        $randomizer = new Randomizer();
        $this->assertSame(0, $randomizer->mtRand(0, 0));
    }
}
