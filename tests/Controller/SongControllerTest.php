<?php

namespace App\Tests\Controller;

use App\Document\Room;
use App\Tests\RoomWebTestCase;
use Exception;
use Generator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

class SongControllerTest extends RoomWebTestCase
{
    protected ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * @dataProvider provideRouteSecuredByJWT
     *
     * @param mixed[] $payload
     */
    public function testRouteIsSecuredByJWT(string $httpMethod, string $route, array $payload = []): void
    {
        $this->client->jsonRequest($httpMethod, $route, $payload);
        $this->assertResponseStatusCodeSame(401);
    }

    private function provideRouteSecuredByJWT(): Generator
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
    }

    /**
     * @dataProvider provideRouteWhereJWTMustBelongToARoom
     *
     * @param mixed[] $payload
     */
    public function testJWTBelongToTheRoom(string $httpMethod, string $route, array $payload = []): void
    {
        $room1 = $this->createRoom();
        $room2 = $this->createRoom();

        $route = str_replace('{roomId}', $room1->getId(), $route);
        $this->client->jsonRequest($httpMethod, $route, $payload, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room2->getHost()->getToken(),
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('JWT Token does not belong to this room', $data['title']);
        $this->assertSame(403, $data['status']);
    }

    private function provideRouteWhereJWTMustBelongToARoom(): Generator
    {
        yield [
            'httpMethod' => Request::METHOD_POST,
            'route' => '/room/{roomId}/song',
            'payload' => [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
        ];
        yield [
            'httpMethod' => Request::METHOD_PATCH,
            'route' => '/room/{roomId}/current-song',
            'payload' => [
                'isPaused' => true,
            ],
        ];
    }

    /**
     * @dataProvider provideRouteThatRequireARole
     *
     * @param mixed[] $payload
     */
    public function testPerformActionWithWrongRole(
        string $httpMethod,
        string $route,
        string $errorMessage,
        array $payload = []
    ): void {
        $room = $this->createRoom();
        $guest = $this->joinRoom($room);

        $route = str_replace('{roomId}', $room->getId(), $route);
        $this->client->jsonRequest($httpMethod, $route, $payload, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$guest->getToken(),
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($errorMessage, $data['title']);
        $this->assertSame(403, $data['status']);
    }

    private function provideRouteThatRequireARole(): Generator
    {
        yield [
            'httpMethod' => Request::METHOD_POST,
            'route' => '/room/{roomId}/song',
            'errorMessage' => "You don't have the permission to add song to this room",
            'payload' => [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
        ];
        yield [
            'httpMethod' => Request::METHOD_DELETE,
            'route' => '/room/{roomId}/song/123456',
            'errorMessage' => "You don't have the permission to delete song in this room",
            [],
        ];
        yield [
            'httpMethod' => Request::METHOD_PATCH,
            'route' => '/room/{roomId}/current-song',
            'errorMessage' => "You don't have the permission to update the current song in this room",
            'payload' => [
                'isPaused' => true,
            ],
        ];
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
