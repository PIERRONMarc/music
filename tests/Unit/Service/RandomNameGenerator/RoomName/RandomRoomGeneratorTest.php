<?php

namespace App\Tests\Unit\Service\RandomNameGenerator\RoomName;

use App\Repository\RoomRepository;
use App\Service\Randomizer\RandomizerInterface;
use App\Service\RandomNameGenerator\RoomName\RandomRoomNameGenerator;
use PHPUnit\Framework\TestCase;

class RandomRoomGeneratorTest extends TestCase
{
    public function testGeneratorReturnCorrectVenue(): void
    {
        $randomizer = $this->createMock(RandomizerInterface::class);
        $randomizer->method('mtRand')->willReturn(0);

        $roomRepository = $this->createMock(RoomRepository::class);
        $roomRepository->method('countRoomWithNameLike')->willReturn(0);

        $generator = new RandomRoomNameGenerator($randomizer, $roomRepository);

        $this->assertSame('Red Rocks', $generator->getName());
    }

    public function testNameIsUniq(): void
    {
        $randomizer = $this->createMock(RandomizerInterface::class);
        $randomizer->method('mtRand')->willReturn(0);

        $roomRepository = $this->createMock(RoomRepository::class);
        $roomRepository->method('countRoomWithNameLike')->willReturn(1);

        $generator = new RandomRoomNameGenerator($randomizer, $roomRepository);

        $this->assertSame('Red Rocks 2', $generator->getName());
    }
}
