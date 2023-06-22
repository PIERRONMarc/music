<?php

namespace App\Service\SongProvider;

use App\DTO\SongDTO;

interface SongProviderInterface
{
    /**
     * Get a song based on the resource passed as an argument
     *
     * @param $resource
     */
    public function getSong($resource): SongDTO;
}
