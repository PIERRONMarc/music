<?php

namespace App\Service\RandomNameGenerator\GuestName;

use App\Document\Room;
use App\Repository\RoomRepository;
use App\Service\Randomizer\RandomizerInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

class RandomGuestNameGenerator
{
    /**
     * @var string[]
     */
    private array $adjectives;

    /**
     * @var string[]
     */
    private array $nouns;

    private RoomRepository $roomRepository;

    private RandomizerInterface $randomizer;

    public function __construct(RandomizerInterface $randomizer, DocumentManager $documentManager)
    {
        $this->randomizer = $randomizer;
        /** @var RoomRepository */
        $roomRepository = $documentManager->getRepository(Room::class);
        $this->roomRepository = $roomRepository;
        $this->adjectives = file(__DIR__.'/adjectives.txt', \FILE_IGNORE_NEW_LINES);
        $this->nouns = file(__DIR__.'/nouns.txt', \FILE_IGNORE_NEW_LINES);
    }

    /**
     * Get a randomly generated guest name and make sure he's uniq for a given room.
     */
    public function getNameForRoom(string $roomId): string
    {
        $name = $this->getName();
        $count = $this->roomRepository->countGuestWithNameLike($name, $roomId);

        if ($count > 0) {
            ++$count;

            return $name.' '.$count;
        }

        return $name;
    }

    /**
     * Get a randomly generated guest name.
     */
    public function getName(): string
    {
        $adjective = $this->adjectives[$this->randomizer->mtRand(0, \count($this->adjectives) - 1)];
        $noun = $this->nouns[$this->randomizer->mtRand(0, \count($this->nouns) - 1)];

        return ucwords("{$adjective} {$noun}");
    }
}
