<?php

namespace App\Tests\Functional\Controller;

use App\Document\Guest;
use App\Tests\Functional\RoomWebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

class AuthorizationTest extends RoomWebTestCase
{
    protected ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
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
        $room = $this->createRoomDocument();
        $guest = $this->joinRoom($room);

        $route = str_replace('{roomId}', $room->getId(), $route);
        $this->client->jsonRequest($httpMethod, $route, $payload, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$guest->getToken(),
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($errorMessage, $data['title']);
        $this->assertSame(403, $data['status']);
    }

    private function provideRouteThatRequireARole(): \Generator
    {
        yield [
            'httpMethod' => Request::METHOD_PATCH,
            'route' => '/room/{roomId}/grant-role/Angry%20ape',
            'errorMessage' => "You don't have the permission to grant roles in this room",
            'payload' => [
                'role' => Guest::ROLE_ADMIN,
            ],
        ];
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
        yield [
            'httpMethod' => Request::METHOD_GET,
            'route' => 'room/{roomId}/next-song',
            'errorMessage' => "You don't have the permission to go to the next song in this room",
        ];
    }

    /**
     * @dataProvider provideRouteWhereJWTMustBelongToARoom
     *
     * @param mixed[] $payload
     */
    public function testJWTBelongToTheRoom(string $httpMethod, string $route, array $payload = []): void
    {
        $room1 = $this->createRoomDocument();
        $room2 = $this->createRoomDocument();

        $route = str_replace('{roomId}', $room1->getId(), $route);
        $this->client->jsonRequest($httpMethod, $route, $payload, [
            'HTTP_AUTHORIZATION' => 'Bearer '.$room2->getHost()->getToken(),
        ]);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('JWT Token does not belong to this room', $data['title']);
        $this->assertSame(403, $data['status']);
    }

    private function provideRouteWhereJWTMustBelongToARoom(): \Generator
    {
        yield [
            'httpMethod' => Request::METHOD_PATCH,
            'route' => '/room/{roomId}/grant-role/Angry%20ape',
            'payload' => [
                'role' => Guest::ROLE_ADMIN,
            ],
        ];
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
        yield [
            'httpMethod' => Request::METHOD_GET,
            'route' => 'room/{roomId}/next-song',
        ];
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

    private function provideRouteSecuredByJWT(): \Generator
    {
        yield [
            'httpMethod' => Request::METHOD_PATCH,
            'route' => '/room/123456/grant-role/Angry%20ape',
            'payload' => [
                'role' => Guest::ROLE_ADMIN,
            ],
        ];
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
            'route' => 'room/23456/next-song',
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
