<?php

namespace App\Mercure\Message;

use Symfony\Component\Mercure\Update;

/**
 * A message object used to build a Mercure update object.
 */
abstract class AbstractMessage
{
    /**
     * @param string[] $topics
     * @param mixed[]  $data
     */
    public function __construct(
        private array $topics,
        private array $data = []
    ) {
    }

    public function buildUpdate(): Update
    {
        return new Update(
            $this->topics,
            json_encode($this->data)
        );
    }
}
