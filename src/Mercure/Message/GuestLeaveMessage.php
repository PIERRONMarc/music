<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
class GuestLeaveMessage extends AbstractRoomActionMessage
{
    /**
     * @param string $roomId id of the room concerned
     * @param string $name   name of the leaving guest
     */
    public function __construct(string $roomId, string $name)
    {
        parent::__construct($roomId, 'guestLeave', [ 'name' => $name ]);
    }
}
