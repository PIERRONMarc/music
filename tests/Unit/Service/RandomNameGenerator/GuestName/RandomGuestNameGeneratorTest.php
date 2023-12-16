<?php

namespace App\Tests\Unit\Service\RandomNameGenerator\GuestName;

use App\Repository\RoomDocumentRepository;
use App\Service\Randomizer\RandomizerInterface;
use App\Service\RandomNameGenerator\GuestName\RandomGuestNameGenerator;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\TestCase;

class RandomGuestNameGeneratorTest extends TestCase
{
    public function testGeneratorReturnCorrectName(): void
    {
        $randomizer = $this->createMock(RandomizerInterface::class);
        $randomizer->method('mtRand')->willReturn(0);

        $roomRepository = $this->createMock(RoomDocumentRepository::class);
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($roomRepository);

        $generator = new RandomGuestNameGenerator($randomizer, $documentManager);

        $this->assertSame('Adorable Aardvark', $generator->getName());
        $this->assertSame('Adorable Aardvark', $generator->getNameForRoom('f28ea2578879389fb4cc2626487dcf79'));
    }

    public function testNameForRoomIsUniq(): void
    {
        $randomizer = $this->createMock(RandomizerInterface::class);
        $randomizer->method('mtRand')->willReturn(0);

        $roomRepository = $this->createMock(RoomDocumentRepository::class);
        $roomRepository->method('countGuestWithNameLike')->willReturn(1);

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($roomRepository);

        $generator = new RandomGuestNameGenerator($randomizer, $documentManager);

        $this->assertSame('Adorable Aardvark 2', $generator->getNameForRoom('f28ea2578879389fb4cc2626487dcf79'));
    }
}
