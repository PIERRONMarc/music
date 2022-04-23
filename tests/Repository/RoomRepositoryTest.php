<?php

namespace App\Tests\Repository;

use App\Document\Room;
use App\Repository\RoomRepository;
use App\Tests\DatabaseTrait;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomRepositoryTest extends KernelTestCase
{
    use DatabaseTrait;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->clearDatabase();
    }

    public function testThatCorrectNumberOfRoomMatchingAStringIsReturned(): void
    {
        $dm = $this->getDocumentManager();
        $dm->persist((new Room())->setName('Red Rocks'));
        $dm->persist((new Room())->setName('Red Rocks 2'));
        $dm->persist((new Room())->setName('Madison Square Garden'));
        $dm->persist((new Room())->setName('Rd Rcks'));
        $dm->flush();

        /** @var RoomRepository */
        $repository = $dm->getRepository(Room::class);
        $this->assertSame(2, $repository->countRoomWithNameLike('Red Rocks'));
    }
}
