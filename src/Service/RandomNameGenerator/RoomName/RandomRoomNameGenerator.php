<?php

namespace App\Service\RandomNameGenerator\RoomName;

use App\Document\Room;
use App\Repository\RoomRepository;
use App\Service\Randomizer\RandomizerInterface;
use App\Service\RandomNameGenerator\RandomNameGeneratorInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;

class RandomRoomNameGenerator implements RandomNameGeneratorInterface
{
    private RandomizerInterface $randomizer;

    private RoomRepository $roomRepository;

    /**
     * @var string[]
     */
    private array $venues;

    public function __construct(RandomizerInterface $randomizer, DocumentManager $documentManager)
    {
        $this->randomizer = $randomizer;
        /** @var RoomRepository */
        $roomRepository = $documentManager->getRepository(Room::class);
        $this->roomRepository = $roomRepository;
        $this->venues = file(__DIR__.'/venues.txt', \FILE_IGNORE_NEW_LINES);
    }

    /**
     * {@inheritDoc}
     *
     * @throws MongoDBException
     */
    public function getName(): string
    {
        $name = $this->venues[$this->randomizer->mtRand(0, \count($this->venues) - 1)];
        $count = $this->roomRepository->countRoomWithNameLike($name);

        if ($count > 0) {
            ++$count;

            return $name.' '.$count;
        }

        return $name;
    }
}