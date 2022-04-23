<?php

namespace App\Controller;

use App\Document\Guest;
use App\Document\Room;
use App\Service\Jwt\TokenFactory;
use App\Service\RandomNameGenerator\GuestName\RandomGuestNameGenerator;
use App\Service\RandomNameGenerator\RoomName\RandomRoomNameGenerator;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoomController extends AbstractController
{
    #[Route('/room', name: 'app_room', methods: ['POST'])]
    public function index(
        DocumentManager $manager,
        RandomRoomNameGenerator $randomRoomNameGenerator,
        RandomGuestNameGenerator $randomGuestNameGenerator,
        TokenFactory $tokenFactory
    ): Response {
        $room = (new Room())
            ->setHost((new Guest())->setUsername($randomGuestNameGenerator->getName()))
            ->setName($randomRoomNameGenerator->getName())
        ;
        $room->setToken($tokenFactory->createToken(['claims' => ['name' => $room->getName()]])->toString());

        $manager->persist($room);
        $manager->flush();

        return $this->json($room);
    }
}
