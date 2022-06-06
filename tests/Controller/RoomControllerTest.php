<?php

namespace App\Tests\Controller;

use App\Document\Room;
use App\Document\Song;
use App\Tests\DatabaseTrait;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Generator;
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
        $this->assertIsString($data['token']);
        $this->assertIsArray($data['songs']);
        $this->assertIsArray($data['guests'][0]); // assert host is stored in guest list

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
        $this->assertIsString($data['room']['id']);
        $this->assertIsString($data['room']['name']);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $data['room']['songs'][0]['url']);
        $this->assertSame($data['guest']['username'], $data['room']['guests'][0]['username'], 'Actual guest is not added to the guest list of the room');
        $this->assertFalse(isset($data['room']['token']));
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
        $this->client->jsonRequest('POST', '/room/'.$room['id'].'/song?roomToken='.$room['token'], [
           'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $data['url']);

        // song must be added in database
        $this->client->request('GET', '/join/'.$room['id']);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $data['room']['songs'][0]['url']);
    }

    public function testAddSongValidation(): void
    {
        $this->client->jsonRequest('POST', '/room');
        $room = json_decode($this->client->getResponse()->getContent(), true);

        // song is not from youtube
        $this->client->jsonRequest('POST', '/room/'.$room['id'].'/song?roomToken='.$room['token'], [
            'url' => 'https://www.dailymotion.com/video/x8amd6r?playlist=x5nmbq',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('url', $data['violations'][0]['property']);
        $this->assertSame('This value is not a valid Youtube video URL.', $data['violations'][0]['message']);

        // url is wrong
        $this->client->jsonRequest('POST', '/room/'.$room['id'].'/song?roomToken='.$room['token'], [
            'url' => 'htts:/www.youtube.com/watch?v=8BCQtYiagvw',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('This value is not a valid Youtube video URL.', $data['violations'][0]['message']);
    }

    /**
     * @dataProvider provideWrongAuthorization
     */
    public function testAddSongAuthorization(?string $jwt, string $expectedTitle): void
    {
        $this->client->jsonRequest('POST', '/room/15686e63b72b3b20aaecd3186ff2c42a/song', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => $jwt,
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($expectedTitle, $data['title']);
        $this->assertSame(401, $data['status']);
    }

    private function provideWrongAuthorization(): Generator
    {
        yield [
            'jwt' => null,
            'expectedTitle' => 'JWT Token not found',
        ];
        yield [
            'jwt' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
            'expectedTitle' => "The Authorization scheme named: 'Bearer' was not found",
        ];
        yield [
            'jwt' => 'bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c',
            'expectedTitle' => 'Invalid JWT Token',
        ];
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
            'HTTP_AUTHORIZATION' => 'Bearer '.$room2['token'],
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('JWT Token does not belong to this room', $data['title']);
        $this->assertSame(403, $data['status']);
    }

    public function testAddSongToANonExistentRoom(): void
    {
        $this->client->jsonRequest('POST', '/room');
        $room = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->jsonRequest('POST', '/room/15686e63b72b3b20aaecd3186ff2c42a/song', [
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room['token'],
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('The room does not exist', $data['title']);
        $this->assertSame(404, $data['status']);
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
