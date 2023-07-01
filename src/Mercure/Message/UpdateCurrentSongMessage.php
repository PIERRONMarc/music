<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
class UpdateCurrentSongMessage extends AbstractRoomActionMessage
{
    /**
     * @param string $roomId   id of the room concerned
     * @param string $url      url of the updated song
     * @param bool   $isPaused state of the update song
     */
    public function __construct(string $roomId, string $url, bool $isPaused)
    {
        parent::__construct($roomId, 'UPDATE_CURRENT_SONG', [
            'url' => $url,
            'isPaused' => $isPaused,
        ]);
    }
}
