<?php

namespace App\Service\RandomNameGenerator\GuestName;

use App\Service\Randomizer\RandomizerInterface;
use App\Service\RandomNameGenerator\RandomNameGeneratorInterface;

class RandomGuestNameGenerator implements RandomNameGeneratorInterface
{
    /**
     * @var string[]
     */
    private array $adjectives;

    /**
     * @var string[]
     */
    private array $nouns;

    private RandomizerInterface $randomizer;

    public function __construct(RandomizerInterface $randomizer)
    {
        $this->randomizer = $randomizer;
        $this->adjectives = file(__DIR__.'/adjectives.txt', \FILE_IGNORE_NEW_LINES);
        $this->nouns = file(__DIR__.'/nouns.txt', \FILE_IGNORE_NEW_LINES);
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        $adjective = $this->adjectives[$this->randomizer->mtRand(0, \count($this->adjectives) - 1)];
        $noun = $this->nouns[$this->randomizer->mtRand(0, \count($this->nouns) - 1)];

        return ucwords("{$adjective} {$noun}");
    }
}
