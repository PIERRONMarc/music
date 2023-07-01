<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
class GuestJoinMessage extends AbstractRoomActionMessage
{
    /**
     * @param string $roomId id of the room concerned
     * @param string $name   name of the joining guest
     * @param string $role   role of the joining guest
     */
    public function __construct(string $roomId, string $name, string $role)
    {
        parent::__construct($roomId, 'GUEST_JOIN', [
            'name' => $name,
            'role' => $role,
        ]);
    }
}
