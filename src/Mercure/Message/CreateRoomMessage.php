<?php

namespace App\Mercure\Message;

class CreateRoomMessage extends AbstractRoomListMessage
{
    public function __construct(string $roomId, string $roomName)
    {
        parent::__construct('createRoom', [
            'id' => $roomId,
            'name' => $roomName,
        ]);
    }
}
