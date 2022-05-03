<?php

namespace App\Controller;

use App\Document\Guest;
use App\Document\Room;
use App\DTO\HttpExceptionDTO;
use App\DTO\JoinRoomDTO;
use App\Service\Jwt\TokenFactory;
use App\Service\RandomNameGenerator\GuestName\RandomGuestNameGenerator;
use App\Service\RandomNameGenerator\RoomName\RandomRoomNameGenerator;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RoomController extends AbstractController
{
    #[Route('/room', name: 'create_room', methods: ['POST'])]
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

    #[Route('/room', name: 'get_all_room', methods: ['GET'])]
    public function getAllRooms(DocumentManager $dm, Request $request): Response
    {
        $page = 0 === $request->query->getInt('page', 1) ? 1 : $request->query->getInt('page', 1);
        $offset = ($page - 1) * 30;
        $rooms = $dm->getRepository(Room::class)->findBy([], [], 30, $offset);

        return $this->json($rooms);
    }

    #[Route('/join/{id}', name: 'join_room', methods: ['GET'])]
    public function joinRoom(
        string $id,
        DocumentManager $dm,
        RandomGuestNameGenerator $randomGuestNameGenerator
    ): Response {
        $guest = (new Guest())->setUsername($randomGuestNameGenerator->getName());
        $room = $dm->getRepository(Room::class)->findOneBy(['id' => str_replace('-', '', $id)]);

        if (!$room) {
            return $this->json((new HttpExceptionDTO())
                ->setDescription('The room '.$id.' does not exist.'), 404);
        }

        $room->addGuest($guest);
        $dm->flush();

        return $this->json((new JoinRoomDTO())
            ->setGuest($guest)
            ->setRoom($room)
        );
    }
}
