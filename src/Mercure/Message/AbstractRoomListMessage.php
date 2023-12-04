<?php

namespace App\Mercure\Message;

/**
 * {@inheritDoc}
 */
abstract class AbstractRoomListMessage extends AbstractMessage
{
    /**
     * @param mixed[] $payload payload sent with the message
     * @param string  $action  action performed
     */
    public function __construct(
        private string $action,
        private array $payload
    ) {
        parent::__construct(
            [
                '/roomList',
            ],
            [
                'action' => $this->action,
                'payload' => $this->payload,
            ]
        );
    }
}
