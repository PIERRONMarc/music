<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
class NextSongMessage extends AbstractRoomActionMessage
{
    /**
     * @param string $roomId id of the room concerned
     */
    public function __construct(string $roomId)
    {
        parent::__construct($roomId, 'nextSong', []);
    }
}