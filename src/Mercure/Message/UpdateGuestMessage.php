<?php

namespace App\Mercure\Message;

class UpdateGuestMessage extends AbstractRoomActionMessage
{
    /**
     * @param string $roomId id of the room concerned
     * @param string $name   name of the updated guest
     * @param string $role   role of the updated guest
     */
    public function __construct(string $roomId, string $name, string $role)
    {
        parent::__construct($roomId, 'updateGuest', [
            'name' => $name,
            'role' => $role,
        ]);
    }
}
