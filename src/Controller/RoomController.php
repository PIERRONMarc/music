<?php

namespace App\Controller;

use App\Document\Guest;
use App\Document\Room;
use App\DTO\JoinRoomDTO;
use App\Service\Jwt\TokenFactory;
use App\Service\RandomNameGenerator\GuestName\RandomGuestNameGenerator;
use App\Service\RandomNameGenerator\RoomName\RandomRoomNameGenerator;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        $guestName = $randomGuestNameGenerator->getName();
        $host = (new Guest())
            ->setName($guestName)
            ->setRole(Guest::ROLE_ADMIN)
        ;
        $room = (new Room())
            ->setHost($host)
            ->addGuest($host)
            ->setName($randomRoomNameGenerator->getName())
        ;

        $manager->persist($room);
        $manager->flush();

        $host->setToken($tokenFactory->createToken([
            'claims' => [
                'guestName' => $guestName,
                'roomId' => $room->getId(),
            ],
        ])->toString());
        $room->setHost($host);

        return $this->json($room, Response::HTTP_CREATED);
    }

    #[Route('/room', name: 'get_all_room', methods: ['GET'])]
    public function getAllRooms(DocumentManager $dm, Request $request): Response
    {
        $page = 0 === $request->query->getInt('page', 1) ? 1 : $request->query->getInt('page', 1);
        $offset = ($page - 1) * 30;
        $rooms = $dm->getRepository(Room::class)->findBy([], [], 30, $offset);

        return $this->json($rooms, Response::HTTP_OK, [], ['groups' => 'get_all_room']);
    }

    #[Route('/join/{id}', name: 'join_room', methods: ['GET'])]
    public function joinRoom(
        string $id,
        DocumentManager $dm,
        RandomGuestNameGenerator $randomGuestNameGenerator,
        TokenFactory $tokenFactory
    ): Response {
        $room = $dm->getRepository(Room::class)->findOneBy(['id' => str_replace('-', '', $id)]);

        if (!$room) {
            throw new NotFoundHttpException('The room '.$id.' does not exist.');
        }

        $guestName = $randomGuestNameGenerator->getNameForRoom($room->getId());
        $guest = (new Guest())
            ->setName($guestName)
            ->setToken($tokenFactory->createToken([
                'claims' => [
                    'guestName' => $guestName,
                    'roomId' => $room->getId(),
                ],
            ])->toString())
        ;

        $room->addGuest($guest);
        $dm->flush();

        return $this->json((new JoinRoomDTO())
            ->setGuest($guest)
            ->setRoom($room)
        );
    }
}
