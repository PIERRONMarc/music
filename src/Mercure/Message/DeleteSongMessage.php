<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
class DeleteSongMessage extends AbstractRoomActionMessage
{
    /**
     * @param string $roomId id of the room concerned
     * @param string $songId id of the song concerned
     */
    public function __construct(string $roomId, string $songId)
    {
        parent::__construct($roomId, 'deleteSong', [
            'songId' => $songId,
        ]);
    }
}
