<?php

namespace App\Mercure\Message;

class AddSongMessage extends AbstractRoomActionMessage
{
    /**
     * @param string $roomId id of the room concerned
     * @param string $songId id of the added song
     * @param string $url    url of the added song
     */
    public function __construct(
        string $roomId,
        string $songId,
        string $url,
        string $title,
        string $author,
        int $lengthInSeconds
    ) {
        parent::__construct($roomId, 'addSong', [
            'id' => $songId,
            'url' => $url,
            'title' => $title,
            'author' => $author,
            'lengthInSeconds' => $lengthInSeconds,
        ]);
    }
}
