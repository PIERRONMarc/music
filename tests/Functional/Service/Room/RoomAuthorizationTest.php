<?php

namespace App\Tests\Functional\Service\Room;

use App\Document\Guest;
use App\Document\Room;
use App\Service\Room\RoomAuthorization;
use App\Tests\Functional\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomAuthorizationTest extends KernelTestCase
{
    use DatabaseTrait;

    public function testGuestIsGranted(): void
    {
        $guest = (new Guest())->setName('Angry ape');
        $host = (new Guest())->setName('Happy ape');
        $room = (new Room())
            ->addGuest($guest)
            ->addGuest($host)
            ->setHost($host)
        ;
        $this->getDocumentManager()->persist($room);
        $this->getDocumentManager()->flush();

        $roomAuthorization = new RoomAuthorization();

        $this->assertTrue($roomAuthorization->guestIsGranted(Guest::ROLE_GUEST, $guest->getName(), $room->getGuests()->toArray()));
        $this->assertFalse($roomAuthorization->guestIsGranted(Guest::ROLE_ADMIN, $guest->getName(), $room->getGuests()->toArray()));
        $this->assertFalse($roomAuthorization->guestIsGranted(Guest::ROLE_ADMIN, 'name of inexistant guest', $room->getGuests()->toArray()));
    }

    /**
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        $this->clearDatabase();
        parent::tearDown();
    }
}
