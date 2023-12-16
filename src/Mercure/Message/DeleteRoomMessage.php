<?php

namespace App\Mercure\Message;

class DeleteRoomMessage extends AbstractRoomListMessage
{
    /**
     * @param string $name name of the joining guest
     */
    public function __construct(string $name)
    {
        parent::__construct('deleteRoom', [
            'name' => $name,
        ]);
    }
}
