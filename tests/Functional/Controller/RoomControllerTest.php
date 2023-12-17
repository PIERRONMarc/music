<?php

namespace App\Tests\Functional\Controller;

use App\Document\Guest as GuestDocument;
use App\Entity\Guest;
use App\Entity\Room;
use App\Tests\Functional\RoomWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        $this->assertSame($room->getId()->toRfc4122(), $data[0]['id']);
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
        $this->assertSame($song->getId()->toRfc4122(), $data['room']['songs'][0]['id']);
        $this->assertSame($song->getUrl(), $data['room']['songs'][0]['url']);
        $this->assertSame($currentSong->getUrl(), $data['room']['currentSong']['url']);
        $this->assertSame($currentSong->getId()->toRfc4122(), $data['room']['currentSong']['id']);
        $this->assertFalse($data['room']['currentSong']['isPaused']);
        $this->assertSame($data['guest']['name'], $data['room']['guests'][1]['name'], 'Actual guest is not added to the guest list of the room');
    }

    public function testJoinARoomThatDoesntExist(): void
    {
        $this->client->jsonRequest(Request::METHOD_GET, '/join/5f897ff3-4f86-406e-bac0-e8e802ddb45a');
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame(404, $data['status']);
        $this->assertSame('The room 5f897ff3-4f86-406e-bac0-e8e802ddb45a does not exist.', $data['title']);
    }

    public function testGrantRoleOnGuest(): void
    {
        $room = $this->createRoom();
        $guest = $this->joinRoom($room);

        $this->client->jsonRequest(
            Request::METHOD_PATCH,
            sprintf('/room/%s/grant-role/%s', $room->getId()->toRfc4122(), $guest->getName()),
            ['role' => GuestDocument::ROLE_ADMIN],
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())],
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $updatedRoom = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());
        foreach ($updatedRoom->getGuests() as $updatedGuest) {
            if ($updatedGuest->getName() == $guest->getName()) {
                $this->assertSame(Guest::ROLE_ADMIN, $updatedGuest->getRole());
            }
        }
    }

    public function testCannotUpdateHostRole(): void
    {
        $room = $this->createRoom();

        $this->client->jsonRequest(Request::METHOD_PATCH, '/room/'.$room->getId().'/grant-role/'.$room->getHost()->getName(), [
            'role' => Guest::ROLE_GUEST,
        ], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame("You can't update the role of the host", $data['title']);

        $updatedRoom = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());
        foreach ($updatedRoom->getGuests() as $updatedGuest) {
            if ($updatedGuest->getName() == $room->getHost()->getName()) {
                $this->assertNotSame(Guest::ROLE_GUEST, $updatedGuest->getRole());
            }
        }
    }

    /**
     * @dataProvider provideWrongPayloadToGrantRoleOnGuest
     *
     * @param mixed[] $payload
     */
    public function testGrantNotDefinedRole(array $payload, string $violationMessage): void
    {
        $room = $this->createRoom();
        $guest = $this->joinRoom($room);

        $this->client->jsonRequest(
            Request::METHOD_PATCH,
            sprintf('/room/%s/grant-role/%s', $room->getId()->toRfc4122(), $guest->getName()),
            $payload,
            ['HTTP_AUTHORIZATION' => sprintf('Bearer %s', $room->getHost()->getToken())],
        );
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertSame('role', $data['violations'][0]['property']);
        $this->assertSame($violationMessage, $data['violations'][0]['message']);
    }

    private function provideWrongPayloadToGrantRoleOnGuest(): \Generator
    {
        yield [
            'payload' => [
                'role' => 'NOT_A_ROLE',
            ],
            'violationMessage' => 'Not a valid role',
        ];
        yield [
            'payload' => [],
            'violationMessage' => 'This value should not be null.',
        ];
    }

    /**
     * @dataProvider provideRouteThatNeedARoom
     *
     * @param mixed[] $payload
     */
    public function testPerformActionOnNonExistentResource(
        string $httpMethod,
        string $route,
        string $errorMessage,
        array $payload = []
    ): void {
        $room = $this->createRoom();

        $route = str_replace('{roomId}', $room->getId(), $route);
        $this->client->jsonRequest($httpMethod, $route, $payload, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room->getHost()->getToken(),
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($errorMessage, $data['title']);
        $this->assertSame(404, $data['status']);
    }

    private function provideRouteThatNeedARoom(): \Generator
    {
        yield [
            'httpMethod' => Request::METHOD_PATCH,
            'route' => '/room/947f3306-4155-476d-bccf-184eba63bc0c/grant-role/Angry%20ape',
            'errorMessage' => 'The room does not exist',
            'payload' => [
                'role' => GuestDocument::ROLE_ADMIN,
            ],
        ];
        yield [
            'httpMethod' => Request::METHOD_PATCH,
            'route' => '/room/{roomId}/grant-role/947f3306-4155-476d-bccf-184eba63bc0c',
            'errorMessage' => 'Guest is not found',
            'payload' => [
                'role' => GuestDocument::ROLE_ADMIN,
            ],
        ];
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
