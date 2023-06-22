<?php

namespace App\DTO;

class SongDTO
{
    public function __construct(
        public string $id,
        public string $lengthInSeconds,
        public string $title,
        public string $author,
    ) {}
}
