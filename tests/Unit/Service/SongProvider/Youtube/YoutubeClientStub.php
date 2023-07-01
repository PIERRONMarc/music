<?php

namespace App\Tests\Unit\Service\SongProvider\Youtube;

use App\DTO\SongDTO;
use App\Service\SongProvider\SongProviderInterface;

class YoutubeClientStub implements SongProviderInterface
{
    public function getSong(mixed $resource): SongDTO
    {
        return new SongDTO(
            'id',
            1,
            'title',
            'author'
        );
    }
}
