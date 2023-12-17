<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Room;
use App\Tests\Functional\RoomWebTestCase;
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

    private function provideRouteThatNeedARoom(): \Generator
    {
        yield [
            'httpMethod' => Request::METHOD_POST,
            'route' => '/room/31d99ea4-326d-4e09-9a08-0c3850d18f8f/song',
            'payload' => [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
        ];
        yield [
            'httpMethod' => Request::METHOD_DELETE,
            'route' => '/room/31d99ea4-326d-4e09-9a08-0c3850d18f8f/song/53925b38-43a7-4072-a41c-1ec28ddf111d',
        ];
        yield [
            'httpMethod' => Request::METHOD_PATCH,
            'route' => '/room/31d99ea4-326d-4e09-9a08-0c3850d18f8f/current-song',
            'payload' => [
                'isPaused' => true,
            ],
        ];
        yield [
            'httpMethod' => Request::METHOD_GET,
            'route' => 'room/31d99ea4-326d-4e09-9a08-0c3850d18f8f/next-song',
        ];
    }

    public function testGivenCurrentSongIsNullWhenIAddSongThenSongIsAddedAsCurrentSong(): void
    {
        $room = $this->createRoom();

        $this->client->jsonRequest(
            Request::METHOD_POST,
            sprintf('/room/%s/song', $room->getId()->toRfc4122()),
            ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())]
        );
        $song = json_decode($this->client->getRequest()->getContent(), true);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $song['url']);

        $updatedRoom = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());

        $this->assertSame('dQw4w9WgXcQ', $updatedRoom->getCurrentSong()->getUrl());
        $this->assertFalse($updatedRoom->getCurrentSong()->isPaused());
        $this->assertSame('title', $updatedRoom->getCurrentSong()->getTitle());
        $this->assertSame('author', $updatedRoom->getCurrentSong()->getAuthor());
        $this->assertSame(120, $updatedRoom->getCurrentSong()->getLengthInSeconds());
    }

    public function testGivenCurrentRoomExistWhenIAddSongThenSongIsAddedInSongList(): void
    {
        $room = $this->createRoom(withCurrentSong: true);

        $this->client->jsonRequest(
            Request::METHOD_POST,
            sprintf('/room/%s/song', $room->getId()->toRfc4122()),
            ['url' => 'https://www.youtube.com/watch?v=fFt0s7crDfo'],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())]
        );
        $song = json_decode($this->client->getRequest()->getContent(), true);
        $this->assertSame('https://www.youtube.com/watch?v=fFt0s7crDfo', $song['url']);

        $updatedRoom = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());

        $song = $updatedRoom->getSongs()->first();
        $this->assertSame('fFt0s7crDfo', $song->getUrl());
        $this->assertFalse($song->isPaused());
        $this->assertSame('title', $song->getTitle());
        $this->assertSame('author', $song->getAuthor());
        $this->assertSame(120, $song->getLengthInSeconds());
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
        $this->client->jsonRequest(
            Request::METHOD_POST,
            sprintf('/room/%s/song', $room->getId()->toRfc4122()),
            $payload,
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())],
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('url', $data['violations'][0]['property']);
        $this->assertSame($violationMessage, $data['violations'][0]['message']);
    }

    private function provideWrongAddSongPayload(): \Generator
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

        $this->client->jsonRequest(
            Request::METHOD_DELETE,
            sprintf('/room/%s/song/%s', $room->getId()->toRfc4122(), $song->getId()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())]);
        $this->assertResponseStatusCodeSame(204);

        $updatedRoom = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());
        $this->assertEmpty($updatedRoom->getSongs());
    }

    public function testCurrentSongIsUpdated(): void
    {
        $room = $this->createRoom();
        $song = $this->addSong($room, true);

        $this->client->jsonRequest(
            Request::METHOD_PATCH,
            sprintf('/room/%s/current-song', $room->getId()->toRfc4122()),
            ['isPaused' => true],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())],
        );
        $currentSong = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($song->getUrl(), $currentSong['url']);
        $this->assertTrue($currentSong['isPaused']);
    }

    public function testUpdateCurrentSongWhenNoCurrentSong(): void
    {
        $room = $this->createRoom();

        $this->client->jsonRequest(
            Request::METHOD_PATCH,
            sprintf('/room/%s/current-song', $room->getId()->toRfc4122()),
            ['isPaused' => true],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())],
        );
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

        $this->client->jsonRequest(
            Request::METHOD_PATCH,
            sprintf('/room/%s/current-song', $room->getId()->toRfc4122()),
            $payload,
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())],
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($violationMessage, $data['violations'][0]['message']);
    }

    private function provideWrongUpdateCurrentSongPayload(): \Generator
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

        $this->client->jsonRequest(
            Request::METHOD_GET,
            sprintf('/room/%s/next-song', $room->getId()->toRfc4122()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())],
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($nextSong->getUrl(), $data['url']);
        $this->assertFalse($data['isPaused']);

        $updatedRoom = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());
        $this->assertEmpty($updatedRoom->getSongs());
    }

    public function testGoToNextSongWhenNoMoreSongs(): void
    {
        $room = $this->createRoom();

        $this->client->jsonRequest(
            Request::METHOD_GET,
            sprintf('/room/%s/next-song', $room->getId()->toRfc4122()),
            [],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())],
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertNull($data);
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        $this->clearDatabase();
        $this->client = null;
        parent::tearDown();
    }
}
