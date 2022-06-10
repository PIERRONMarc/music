<?php

namespace App\Tests\Service\Room;

use App\Document\Guest;
use App\Document\Room;
use App\Service\Room\RoomAuthorization;
use App\Tests\DatabaseTrait;
use Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoomAuthorizationTest extends KernelTestCase
{
    use DatabaseTrait;

    public function testGuestIsGranted(): void
    {
        $guest = (new Guest())->setUsername('Angry ape');
        $host = (new Guest())->setUsername('Happy ape');
        $room = (new Room())
            ->addGuest($guest)
            ->addGuest($host)
            ->setHost($host)
        ;
        $this->getDocumentManager()->persist($room);
        $this->getDocumentManager()->flush();

        $roomAuthorization = new RoomAuthorization();

        $this->assertTrue($roomAuthorization->guestIsGranted(Guest::ROLE_GUEST, $guest->getUsername(), $room->getGuests()->toArray()));
        $this->assertFalse($roomAuthorization->guestIsGranted(Guest::ROLE_ADMIN, $guest->getUsername(), $room->getGuests()->toArray()));
        $this->assertFalse($roomAuthorization->guestIsGranted(Guest::ROLE_ADMIN, 'name of inexistant guest', $room->getGuests()->toArray()));
    }

    /**
     * @throws Exception
     */
    protected function tearDown(): void
    {
        $this->clearDatabase();
        parent::tearDown();
    }
}
