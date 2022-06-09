<?php

namespace App\Tests\Controller;

use App\Document\Room;
use App\Document\Song;
use App\Tests\DatabaseTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoomControllerTest extends WebTestCase
{
    use DatabaseTrait;

    protected ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testRoomCreation(): void
    {
        $this->client->request('POST', '/room');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $uuidPattern = "/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i";
        $this->assertMatchesRegularExpression($uuidPattern, $data['id'] ?? false, 'Invalid UUID');
        $this->assertIsString($data['name']);
        $this->assertIsString($data['host']['username']);
        $this->assertIsString($data['host']['token']);
        $this->assertSame('ADMIN', $data['host']['role']);
        $this->assertIsArray($data['songs']);
        $this->assertIsArray($data['guests'][0]);

        $this->assertResponseIsSuccessful();
    }

    public function testGettingAllRoom(): void
    {
        $dm = $this->getDocumentManager();
        $dm->persist((new Room())->setName('Red Rocks'));
        $dm->flush();

        $this->client->request('GET', '/room');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Red Rocks', $data[0]['name']);
    }

    public function testGettingAllRoomIsPaginated(): void
    {
        $this->storeRooms(30);
        $dm = $this->getDocumentManager();
        $dm->persist((new Room())->setName('Madison Square Garden'));
        $dm->flush();

        $this->client->request('GET', '/room?page=2');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Madison Square Garden', $data[0]['name']);
    }

    public function testPageQueryParameterIsValidated(): void
    {
        $this->client->request('GET', '/room?page=x');
        $this->assertResponseIsSuccessful();
    }

    /**
     * @return mixed[]
     *
     * @throws MongoDBException
     */
    private function storeRooms(int $numberOfRooms): array
    {
        $rooms = [];

        $dm = $this->getDocumentManager();
        for ($i = 0; $i < $numberOfRooms; ++$i) {
            $room = (new Room())->setName((string) $i);
            $dm->persist($room);
            $rooms[] = $room;
        }
        $dm->flush();

        return $rooms;
    }

    public function testJoinRoomAsAGuest(): void
    {
        $room = (new Room())
            ->setName('Madison Square Garden')
            ->addSong((new Song())->setUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'))
        ;
        $dm = $this->getDocumentManager();
        $dm->persist($room);
        $dm->flush();

        $this->client->request('GET', '/join/'.$room->getId());

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsString($data['guest']['username']);
        $this->assertSame('GUEST', $data['guest']['role']);
        $this->assertIsString($data['guest']['token']);
        $this->assertIsString($data['room']['id']);
        $this->assertIsString($data['room']['name']);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $data['room']['songs'][0]['url']);
        $this->assertSame($data['guest']['username'], $data['room']['guests'][0]['username'], 'Actual guest is not added to the guest list of the room');
    }

    public function testJoinARoomThatDoesntExist(): void
    {
        $room = (new Room())
            ->setName('Madison Square Garden')
            ->addSong((new Song())->setUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'))
        ;
        $dm = $this->getDocumentManager();
        $dm->persist($room);
        $dm->flush();

        $this->client->jsonRequest('GET', '/join/15686e63b72b3b20aaecd3186ff2c42a');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(404, $data['status']);
        $this->assertSame('The room 15686e63b72b3b20aaecd3186ff2c42a does not exist.', $data['title']);
    }

    public function testAddASongToARoom(): void
    {
        $this->client->jsonRequest('POST', '/room');
        $room = json_decode($this->client->getResponse()->getContent(), true);

        // song must be returned
        $this->client->jsonRequest('POST', '/room/'.$room['id'].'/song', [
           'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['host']['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $data['url']);
        $this->assertIsString($data['id']);

        // song must be added in database
        $this->client->request('GET', '/join/'.$room['id']);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $data['room']['songs'][0]['url']);
    }

    public function testAddSongRouteIsSecuredByJWT(): void
    {
        $this->client->jsonRequest('POST', '/room/123456/song', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testAddSongValidation(): void
    {
        $this->client->jsonRequest('POST', '/room');
        $room = json_decode($this->client->getResponse()->getContent(), true);

        // song is not from youtube
        $this->client->jsonRequest('POST', '/room/'.$room['id'].'/song', [
            'url' => 'https://www.dailymotion.com/video/x8amd6r?playlist=x5nmbq',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['host']['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('url', $data['violations'][0]['property']);
        $this->assertSame('This value is not a valid Youtube video URL.', $data['violations'][0]['message']);

        // url is wrong
        $this->client->jsonRequest('POST', '/room/'.$room['id'].'/song', [
            'url' => 'htts:/www.youtube.com/watch?v=8BCQtYiagvw',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['host']['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('This value is not a valid Youtube video URL.', $data['violations'][0]['message']);
    }

    public function testAddSongJWTBelongToTheRoom(): void
    {
        $this->client->jsonRequest('POST', '/room');
        $room1 = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest('POST', '/room');
        $room2 = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest('POST', '/room/'.$room1['id'].'/song', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room2['host']['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('JWT Token does not belong to this room', $data['title']);
        $this->assertSame(403, $data['status']);
    }

    public function testAddSongWithWrongRole(): void
    {
        $this->client->jsonRequest('POST', '/room');
        $room = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/join/'.$room['id']);
        $guest = json_decode($this->client->getResponse()->getContent(), true)['guest'];

        $this->client->jsonRequest('POST', '/room/'.$room['id'].'/song', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$guest['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame("You don't have the permission to add song to this room", $data['title']);
        $this->assertSame(403, $data['status']);
    }

    public function testAddSongToANonExistentRoom(): void
    {
        $this->client->jsonRequest('POST', '/room');
        $room = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest('POST', '/room/15686e63b72b3b20aaecd3186ff2c42a/song', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['host']['token'],
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('The room does not exist', $data['title']);
        $this->assertSame(404, $data['status']);
    }

    public function testDeleteSong(): void
    {
        $this->client->jsonRequest('POST', '/room');
        $room = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest('POST', '/room/'.$room['id'].'/song', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['host']['token'],
        ]);
        $song = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest('DELETE', '/room/'.$room['id'].'/song/'.$song['id'], [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['host']['token'],
        ]);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteSongRouteIsSecuredByJWT(): void
    {
        $this->client->jsonRequest('DELETE', '/room/123456/song/123456');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testDeleteSongWithWrongRole(): void
    {
        $this->client->jsonRequest('POST', '/room');
        $room = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', '/join/'.$room['id']);
        $guest = json_decode($this->client->getResponse()->getContent(), true)['guest'];

        $this->client->jsonRequest('POST', '/room/'.$room['id'].'/song', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['host']['token'],
        ]);
        $song = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest('DELETE', '/room/'.$room['id'].'/song/'.$song['id'], [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$guest['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame("You don't have the permission to add song to this room", $data['title']);
        $this->assertSame(403, $data['status']);
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
