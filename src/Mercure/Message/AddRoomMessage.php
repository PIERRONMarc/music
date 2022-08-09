<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
class AddRoomMessage extends AbstractMessage
{
    /**
     * @param string $id   id of the added room
     * @param string $name name of the added room
     */
    public function __construct(string $id, string $name)
    {
        parent::__construct(['/room'], [
            'action' => 'ADD_ROOM',
            'payload' => [
                'id' => $id,
                'name' => $name,
            ],
        ]);
    }
}
