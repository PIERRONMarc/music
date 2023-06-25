<?php

namespace App\Service\SongProvider;

use App\DTO\SongDTO;

interface SongProviderInterface
{
    /**
     * Get a song based on the resource passed as an argument.
     */
    public function getSong(mixed $resource): SongDTO;
}
