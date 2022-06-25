<?php

namespace App\Tests\Controller;

use App\Tests\DatabaseTrait;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SongControllerTest extends WebTestCase
{
    use DatabaseTrait;

    protected ?KernelBrowser $client = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
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

        // song must be added in database and running as it's the only song in the playlist
        $this->client->request('GET', '/join/'.$room['id']);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $data['room']['currentSong']['url']);
        $this->assertFalse($data['room']['currentSong']['isPaused']);
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

        $this->client->jsonRequest('GET', '/join/'.$room['id']);
        $joinRoomDTO = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEmpty($joinRoomDTO['room']['songs']);
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
