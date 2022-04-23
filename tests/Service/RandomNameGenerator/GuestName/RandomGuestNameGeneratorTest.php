<?php

namespace App\Tests\Service\RandomNameGenerator\GuestName;

use App\Service\Randomizer\RandomizerInterface;
use App\Service\RandomNameGenerator\GuestName\RandomGuestNameGenerator;
use PHPUnit\Framework\TestCase;

class RandomGuestNameGeneratorTest extends TestCase
{
    public function testGeneratorReturnCorrectVenue(): void
    {
        $randomizer = $this->createMock(RandomizerInterface::class);
        $randomizer->method('mtRand')->willReturn(0);

        $generator = new RandomGuestNameGenerator($randomizer);

        $this->assertSame('Adorable Aardvark', $generator->getName());
    }
}
