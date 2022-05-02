<?php

namespace App\Tests\Controller;

use App\Document\Room;
use App\Tests\DatabaseTrait;
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
        $this->assertIsString($data['token']);
        $this->assertIsArray($data['songs']);
        $this->assertIsArray($data['guests']);

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

    private function storeRooms(int $numberOfRooms): void
    {
        $dm = $this->getDocumentManager();
        for ($i = 0; $i < $numberOfRooms; ++$i) {
            $dm->persist((new Room())->setName((string) $i));
        }
        $dm->flush();
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
