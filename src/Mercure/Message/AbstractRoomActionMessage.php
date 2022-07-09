<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
abstract class AbstractRoomActionMessage extends AbstractMessage
{
    /**
     * @param string  $roomId  roomId to generate topic of the message
     * @param mixed[] $payload payload sent with the message
     * @param string  $action  action performed
     */
    public function __construct(
        private string $roomId,
        private string $action,
        private array $payload
    ) {
        parent::__construct(
            [
                '/room/'.$this->roomId,
            ],
            [
                'action' => $this->action,
                'payload' => $this->payload,
            ]
        );
    }
}
