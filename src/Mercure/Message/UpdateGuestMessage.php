<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
class UpdateGuestMessage extends AbstractRoomActionMessage
{
    /**
     * @param string $roomId id of the room concerned
     * @param string $name   name of the updated guest
     * @param string $role   role of the updated guest
     */
    public function __construct(string $roomId, string $name, string $role)
    {
        parent::__construct($roomId, 'UPDATE_GUEST', [
            'name' => $name,
            'role' => $role,
        ]);
    }
}
