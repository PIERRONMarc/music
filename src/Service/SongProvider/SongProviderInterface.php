<?php

namespace App\Service\SongProvider;

use App\DTO\SongDTO;

interface SongProviderInterface
{
    public function getSong(mixed $resource): SongDTO;

    /**
     * @return array<SongDTO>
     */
    public function getSongsFromPlaylist(mixed $resource): array;
}
