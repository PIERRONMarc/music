<?php

namespace App\Tests\Unit\Mercure\Message;

use App\Mercure\Message\UpdateCurrentSongMessage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\Update;

class TestUpdateCurrentSongMessage extends TestCase
{
    public function testUpdateIsCorrect(): void
    {
        $message = new UpdateCurrentSongMessage(
            '1',
            'https://www.youtube.com/watch?v=8BCQtYiagvw',
            true
        );

        $update = $message->buildUpdate();

        $this->assertInstanceOf(Update::class, $update);
        $this->assertSame('/room/1', $update->getTopics()[0]);

        $data = $update->getData();
        $data = json_decode($data, true);

        $this->assertSame('updateCurrentSong', $data['action']);
        $this->assertSame([
            'url' => 'https://www.youtube.com/watch?v=8BCQtYiagvw',
            'isPaused' => true,
        ], $data['payload']);
    }
}
