<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Guest;
use App\Entity\Room;
use App\Repository\RoomRepository;
use App\Tests\Functional\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomRepositoryTest extends KernelTestCase
{
    use DatabaseTrait;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testThatCorrectNumberOfRoomMatchingAStringIsReturned(): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist((new Room())->setName('Red Rocks')->setHost((new Guest())->setName('John')));
        $entityManager->persist((new Room())->setName('Red Rocks 2')->setHost((new Guest())->setName('John')));
        $entityManager->persist((new Room())->setName('Madison Square Garden')->setHost((new Guest())->setName('John')));
        $entityManager->persist((new Room())->setName('Rd Rcks')->setHost((new Guest())->setName('John')));
        $entityManager->flush();

        /** @var RoomRepository $repository */
        $repository = $entityManager->getRepository(Room::class);
        $this->assertSame(2, $repository->countRoomWithNameLike('Red Rocks'));
    }

    public function testThatCorrectNumberOfGuestMatchingAStringIsReturned(): void
    {
        $entityManager = $this->getEntityManager();
        $room = (new Room())
            ->setName('Red Rocks')
            ->setHost((new Guest())->setName('Adorable Advaark'))
            ->addGuest((new Guest())->setName('Adorable Advaark'))
            ->addGuest((new Guest())->setName('Adorable Advaark 2'))
            ->addGuest((new Guest())->setName('Adorable Ape'))
        ;
        $entityManager->persist($room);
        $entityManager->flush();

        /** @var RoomRepository $repository */
        $repository = $entityManager->getRepository(Room::class);
        $this->assertSame(2, $repository->countGuestWithNameLike('Adorable Advaark', $room->getId()->toRfc4122()));
        $this->assertSame(0, $repository->countGuestWithNameLike('Adorable Advaark', '76413dfe-b0d0-4828-8ff7-768003ea1d58'));
    }
}
