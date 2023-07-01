<?php

namespace App\Tests\Functional\Controller;

use App\Document\Room;
use App\Tests\Functional\RoomWebTestCase;
use Exception;
use Generator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SongControllerTest extends RoomWebTestCase
{
    protected ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @dataProvider provideRouteThatNeedARoom
     *
     * @param mixed[] $payload
     */
    public function testPerformActionOnNonExistentRoom(string $httpMethod, string $route, array $payload = []): void
    {
        $room = $this->createRoom();

        $this->client->jsonRequest($httpMethod, $route, $payload, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('The room does not exist', $data['title']);
        $this->assertSame(404, $data['status']);
    }

    private function provideRouteThatNeedARoom(): Generator
    {
        yield [
            'httpMethod' => Request::METHOD_POST,
            'route' => '/room/123456/song',
            'payload' => [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
        ];
        yield [
            'httpMethod' => Request::METHOD_DELETE,
            'route' => '/room/123456/song/123456',
        ];
        yield [
            'httpMethod' => Request::METHOD_PATCH,
            'route' => '/room/123456/current-song',
            'payload' => [
                'isPaused' => true,
            ],
        ];
        yield [
            'httpMethod' => Request::METHOD_GET,
            'route' => 'room/123456/next-song',
        ];
    }

    public function testAddASongToARoom(): void
    {
        $room = $this->createRoom();

        $this->client->jsonRequest(Request::METHOD_POST, '/room/'.$room->getId().'/song', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);
        $song = json_decode($this->client->getRequest()->getContent(), true);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $song['url']);

        $updatedRoom = $this->getDocumentManager()->getRepository(Room::class)->findOneBy(['id' => $room->getId()]);

        // song must be added in database and running as it's the only song in the playlist
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $updatedRoom->getCurrentSong()->getUrl());
        $this->assertFalse($updatedRoom->getCurrentSong()->getIsPaused());
        $this->assertSame('dQw4w9WgXcQ_title', $updatedRoom->getCurrentSong()->getTitle());
        $this->assertSame('dQw4w9WgXcQ_author', $updatedRoom->getCurrentSong()->getAuthor());
        $this->assertSame(1, $updatedRoom->getCurrentSong()->getLengthInSeconds());
    }

    public function testWhenAddingAnUnexistingSongThenNotFoundExceptionIsThrowed(): void
    {
        $room = $this->createRoom();

        $this->client->jsonRequest(Request::METHOD_POST, '/room/'.$room->getId().'/song', [
            'url' => 'https://www.youtube.com/watch?v=should_throw_not_found',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Song not found', $data['title']);
        $this->assertSame(404, $data['status']);
    }

    /**
     * @dataProvider provideWrongAddSongPayload
     *
     * @param mixed[] $payload
     */
    public function testAddSongValidation(array $payload, string $violationMessage): void
    {
        $room = $this->createRoom();

        // song is not from YouTube
        $this->client->jsonRequest(Request::METHOD_POST, '/room/'.$room->getId().'/song', $payload, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('url', $data['violations'][0]['property']);
        $this->assertSame($violationMessage, $data['violations'][0]['message']);
    }

    private function provideWrongAddSongPayload(): Generator
    {
        yield [
            'payload' => [
                'url' => 'https://www.dailymotion.com/video/x8amd6r?playlist=x5nmbq',
            ],
            'violationMessage' => 'This value is not a valid Youtube video URL.',
        ];
        yield [
            'payload' => [
                'url' => 'htts:/www.youtube.com/watch?v=8BCQtYiagvw',
            ],
            'violationMessage' => 'This value is not a valid Youtube video URL.',
        ];
        yield [
            'payload' => [],
            'violationMessage' => 'This value should not be null.',
        ];
    }

    public function testDeleteSong(): void
    {
        $room = $this->createRoom();
        $song = $this->addSong($room);

        $this->client->jsonRequest(Request::METHOD_DELETE, '/room/'.$room->getId().'/song/'.$song->getId(), [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);
        $this->assertResponseStatusCodeSame(204);

        $updatedRoom = $this->getDocumentManager()->getRepository(Room::class)->findOneBy(['id' => $room->getId()]);
        $this->assertEmpty($updatedRoom->getSongs());
    }

    public function testCurrentSongIsUpdated(): void
    {
        $room = $this->createRoom();
        $song = $this->addSong($room, true);

        $this->client->jsonRequest(Request::METHOD_PATCH, '/room/'.$room->getId().'/current-song', [
            'isPaused' => true,
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);
        $currentSong = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($song->getUrl(), $currentSong['url']);
        $this->assertTrue($currentSong['isPaused']);
    }

    public function testUpdateCurrentSongWhenNoCurrentSong(): void
    {
        $room = $this->createRoom();

        $this->client->jsonRequest(Request::METHOD_PATCH, '/room/'.$room->getId().'/current-song', [
            'isPaused' => true,
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('There is no current song', $data['title']);
        $this->assertSame(404, $data['status']);
    }

    /**
     * @dataProvider provideWrongUpdateCurrentSongPayload
     *
     * @param mixed[] $payload
     */
    public function testUpdateCurrentSongValidation(array $payload, string $violationMessage): void
    {
        $room = $this->createRoom();
        $this->addSong($room, true);

        $this->client->jsonRequest(Request::METHOD_PATCH, '/room/'.$room->getId().'/current-song', $payload, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($violationMessage, $data['violations'][0]['message']);
    }

    private function provideWrongUpdateCurrentSongPayload(): Generator
    {
        yield [
            'payload' => [],
            'violationMessage' => 'This value should not be null.',
        ];
        yield [
            'payload' => [
                'isPaused' => 'azezae',
            ],
            'violationMessage' => 'The selected choice is invalid.',
        ];
    }

    public function testGoToTheNextSong(): void
    {
        $room = $this->createRoom();
        $this->addSong($room, true);
        $nextSong = $this->addSong($room, false, [
            'url' => 'https://www.youtube.com/watch?v=pAgnJDJN4VA&ab_channel=acdcVEVO',
        ]);

        $this->client->jsonRequest(Request::METHOD_GET, '/room/'.$room->getId().'/next-song', [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($nextSong->getUrl(), $data['url']);
        $this->assertFalse($data['isPaused']);

        // assert playlist is updated
        $updatedRoom = $this->getDocumentManager()->getRepository(Room::class)->findOneBy(['id' => $room->getId()]);
        $this->assertEmpty($updatedRoom->getSongs());
    }

    public function testGoToNextSongWhenNoMoreSongs(): void
    {
        $room = $this->createRoom();

        $this->client->jsonRequest(Request::METHOD_GET, '/room/'.$room->getId().'/next-song', [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertSame('There is no song to go', $data['title']);
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        $this->clearDatabase();
        $this->client = null;
        parent::tearDown();
    }
}
