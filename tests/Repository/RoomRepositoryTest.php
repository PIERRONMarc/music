<?php

namespace App\Tests\Repository;

use App\Document\Guest;
use App\Document\Room;
use App\Document\Song;
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

    public function testThatCorrectNumberOfGuestMatchingAStringIsReturned(): void
    {
        $dm = $this->getDocumentManager();
        $room = (new Room())
            ->setName('Red Rocks')
            ->addGuest((new Guest())->setName('Adorable Advaark'))
            ->addGuest((new Guest())->setName('Adorable Advaark 2'))
            ->addGuest((new Guest())->setName('Adorable Ape'))
        ;
        $dm->persist($room);
        $dm->flush();

        /** @var RoomRepository */
        $repository = $dm->getRepository(Room::class);
        $this->assertSame(2, $repository->countGuestWithNameLike('Adorable Advaark', $room->getId()));
        $this->assertSame(0, $repository->countGuestWithNameLike('Adorable Advaark', 'room that does not exist'));
    }

    public function testDeleteSong(): void
    {
        $song = (new Song())->setUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $room = (new Room())
            ->setName('Red Rocks')
            ->addSong($song)
        ;
        $dm = $this->getDocumentManager();
        $room2 = (new Room())
            ->setName('Red Rocks')
            ->addSong($song)
        ;
        $dm->persist($room2);
        $dm->persist($room);
        $dm->flush();

        /** @var RoomRepository */
        $repository = $dm->getRepository(Room::class);
        $updatedRoom = $repository->deleteSong($room->getId(), $song->getId());

        $this->assertEmpty($updatedRoom->getSongs()->toArray());
        $this->assertNull($repository->deleteSong('123', '123')); // assert with wrong ObjectId format for $songId parameter
        $this->assertNull($repository->deleteSong('123', '6290ad1746e25627850c0982'));
    }
}
