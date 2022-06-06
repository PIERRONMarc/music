<?php

namespace App\Tests\Service\RandomNameGenerator\GuestName;

use App\Repository\RoomRepository;
use App\Service\Randomizer\RandomizerInterface;
use App\Service\RandomNameGenerator\GuestName\RandomGuestNameGenerator;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\TestCase;

class RandomGuestNameGeneratorTest extends TestCase
{
    public function testGeneratorReturnCorrectUsername(): void
    {
        $randomizer = $this->createMock(RandomizerInterface::class);
        $randomizer->method('mtRand')->willReturn(0);

        $roomRepository = $this->createMock(RoomRepository::class);
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($roomRepository);

        $generator = new RandomGuestNameGenerator($randomizer, $documentManager);

        $this->assertSame('Adorable Aardvark', $generator->getUsername());
        $this->assertSame('Adorable Aardvark', $generator->getUsernameForRoom('f28ea2578879389fb4cc2626487dcf79'));
    }

    public function testUsernameForRoomIsUniq(): void
    {
        $randomizer = $this->createMock(RandomizerInterface::class);
        $randomizer->method('mtRand')->willReturn(0);

        $roomRepository = $this->createMock(RoomRepository::class);
        $roomRepository->method('countGuestWithNameLike')->willReturn(1);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($roomRepository);

        $generator = new RandomGuestNameGenerator($randomizer, $documentManager);

        $this->assertSame('Adorable Aardvark 2', $generator->getUsernameForRoom('f28ea2578879389fb4cc2626487dcf79'));
    }
}
