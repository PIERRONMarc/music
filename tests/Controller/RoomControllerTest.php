<?php

namespace App\Tests\Controller;

use App\Tests\RoomWebTestCase;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

class RoomControllerTest extends RoomWebTestCase
{
    protected ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testRoomCreation(): void
    {
        $this->client->request(Request::METHOD_POST, '/room');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $uuidPattern = "/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i";
        $this->assertMatchesRegularExpression($uuidPattern, $data['id'] ?? false, 'Invalid UUID');
        $this->assertIsString($data['name']);
        $this->assertIsString($data['host']['name']);
        $this->assertIsString($data['host']['token']);
        $this->assertSame('ADMIN', $data['host']['role']);
        $this->assertIsArray($data['songs']);
        $this->assertIsArray($data['guests'][0]);

        $this->assertResponseIsSuccessful();
    }

    public function testGettingAllRoom(): void
    {
        $room = $this->createRoom();

        $this->client->request(Request::METHOD_GET, '/room');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($room->getName(), $data[0]['name']);
    }

    public function testGettingAllRoomIsPaginated(): void
    {
        $rooms = $this->storeRooms(31);

        $this->client->request(Request::METHOD_GET, '/room?page=2');

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($rooms[30]->getName(), $data[0]['name']);
    }

    public function testPageQueryParameterIsValidated(): void
    {
        $this->client->request(Request::METHOD_GET, '/room?page=x');
        $this->assertResponseIsSuccessful();
    }

    public function testJoinRoomAsAGuest(): void
    {
        $room = $this->createRoom();
        $song = $this->addSong($room);
        $currentSong = $this->addSong($room, true);

        $this->client->request(Request::METHOD_GET, '/join/'.$room->getId());

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsString($data['guest']['name']);
        $this->assertSame('GUEST', $data['guest']['role']);
        $this->assertIsString($data['guest']['token']);
        $this->assertIsString($data['room']['id']);
        $this->assertIsString($data['room']['name']);
        $this->assertSame($song->getId(), $data['room']['songs'][0]['id']);
        $this->assertSame($song->getUrl(), $data['room']['songs'][0]['url']);
        $this->assertSame($currentSong->getUrl(), $data['room']['currentSong']['url']);
        $this->assertSame($currentSong->getId(), $data['room']['currentSong']['id']);
        $this->assertFalse($data['room']['currentSong']['isPaused']);
        $this->assertSame($data['guest']['name'], $data['room']['guests'][1]['name'], 'Actual guest is not added to the guest list of the room');
    }

    public function testJoinARoomThatDoesntExist(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/join/15686e63b72b3b20aaecd3186ff2c42a');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(404, $data['status']);
        $this->assertSame('The room 15686e63b72b3b20aaecd3186ff2c42a does not exist.', $data['title']);
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
