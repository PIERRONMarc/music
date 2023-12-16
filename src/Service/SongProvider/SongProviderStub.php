<?php

namespace App\Service\SongProvider;

use App\DTO\SongDTO;

class SongProviderStub implements SongProviderInterface
{
    public function getSong(mixed $resource): SongDTO
    {
        return new SongDTO(
            id: $resource,
            lengthInSeconds: 120,
            title: 'title',
            author: 'author'
        );
    }

    public function getSongsFromPlaylist(mixed $resource): array
    {
        return [
            new SongDTO(
                id: $resource,
                lengthInSeconds: 120,
                title: 'title',
                author: 'author'
            ),
        ];
    }
}
