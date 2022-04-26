<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoomControllerTest extends WebTestCase
{
    protected ?KernelBrowser $client = null;

    public function getClient(): KernelBrowser
    {
        if (!$this->client) {
            $this->client = static::createClient();
        }

        return $this->client;
    }

    public function testRoomCreation(): void
    {
        $this->getClient()->request('POST', '/room');
        $data = json_decode($this->getClient()->getResponse()->getContent(), true);

        $uuidPattern = "/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i";
        $this->assertMatchesRegularExpression($uuidPattern, $data['id'] ?? false, 'Invalid UUID');
        $this->assertIsString($data['name']);
        $this->assertIsString($data['host']['username']);
        $this->assertIsString($data['token']);
        $this->assertIsArray($data['songs']);
        $this->assertIsArray($data['guests']);

        $this->assertResponseIsSuccessful();
    }
}
