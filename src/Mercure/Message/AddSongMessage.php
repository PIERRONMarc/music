<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
class AddSongMessage extends AbstractRoomActionMessage
{
    /**
     * @param string $roomId id of the room concerned
     * @param string $songId id of the added song
     * @param string $url    url of the added song
     */
    public function __construct(string $roomId, string $songId, string $url)
    {
        parent::__construct($roomId, 'addSong', [
            'songId' => $songId,
            'url' => $url,
        ]);
    }
}
