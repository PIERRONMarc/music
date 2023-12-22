<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Room;
use App\Tests\Functional\RoomWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

class GuestControllerTest extends RoomWebTestCase
{
    protected ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testWhenLeavingRoomThenItRemovesGuestFromGuestList(): void
    {
        $room = $this->createRoom();
        $guest = $this->joinRoom($room);

        self::assertCount(2, $room->getGuests());

        $this->client->jsonRequest(
            Request::METHOD_POST,
            '/room/leave',
            [
                'token' => $guest->getToken(),
            ],
        );

        $updatedRoom = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());
        self::assertCount(1, $updatedRoom->getGuests());
        self::assertResponseIsSuccessful();
    }

    public function testWhenEverybodyLeaveRoomThenItDeletesRoom(): void
    {
        $room = $this->createRoom();
        $this->addSong($room, true);
        $this->addSong($room);
        $roomId = $room->getId()->toRfc4122();

        $this->client->jsonRequest(
            Request::METHOD_POST,
            '/room/leave',
            [
                'token' => $room->getHost()->getToken(),
            ],
        );

        $updatedRoom = $this->getEntityManager()->getRepository(Room::class)->findOneById($roomId);
        self::assertResponseIsSuccessful();
        self::assertNull($updatedRoom);
    }

    public function testWhenAdminLeaveRoomThenItSelectAnotherAdmin(): void
    {
        $room = $this->createRoom();
        $host = $room->getHost();
        $this->joinRoom($room);

        $this->client->jsonRequest(
            Request::METHOD_POST,
            '/room/leave',
            [
                'token' => $room->getHost()->getToken(),
                'roomId' => $room->getId(),
            ],
        );

        self::assertResponseIsSuccessful();
        $updatedRoom = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());
        self::assertTrue($host->getName() !== $updatedRoom->getHost()->getName());
    }
}
