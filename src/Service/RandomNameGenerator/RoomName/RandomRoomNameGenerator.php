<?php

namespace App\Service\RandomNameGenerator\RoomName;

use App\Repository\RoomRepository;
use App\Service\Randomizer\RandomizerInterface;

class RandomRoomNameGenerator
{
    private RandomizerInterface $randomizer;

    private RoomRepository $roomRepository;

    /**
     * @var string[]
     */
    private array $venues;

    public function __construct(RandomizerInterface $randomizer, RoomRepository $roomRepository)
    {
        $this->randomizer = $randomizer;
        $this->roomRepository = $roomRepository;
        $this->venues = file(__DIR__.'/venues.txt', \FILE_IGNORE_NEW_LINES);
    }

    /**
     * Get a randomly generated name.
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
