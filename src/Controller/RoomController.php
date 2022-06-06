<?php

namespace App\Controller;

use App\Document\Guest;
use App\Document\Room;
use App\Document\Song;
use App\DTO\JoinRoomDTO;
use App\Exception\FormHttpException;
use App\Form\SongType;
use App\Service\Jwt\TokenFactory;
use App\Service\RandomNameGenerator\GuestName\RandomGuestNameGenerator;
use App\Service\RandomNameGenerator\RoomName\RandomRoomNameGenerator;
use Doctrine\ODM\MongoDB\DocumentManager;
use Lcobucci\JWT\Token\Plain;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
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
        $username = $randomGuestNameGenerator->getUsername();
        $host = (new Guest())
            ->setUsername($username)
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
                'username' => $username,
                'roomId' => $room->getId(),
            ],
        ])->toString());
        $room->setHost($host);

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
        RandomGuestNameGenerator $randomGuestNameGenerator,
        TokenFactory $tokenFactory
    ): Response {
        $room = $dm->getRepository(Room::class)->findOneBy(['id' => str_replace('-', '', $id)]);

        if (!$room) {
            throw new NotFoundHttpException('The room '.$id.' does not exist.');
        }

        $username = $randomGuestNameGenerator->getUsernameForRoom($room->getId());
        $guest = (new Guest())
            ->setUsername($username)
            ->setToken($tokenFactory->createToken([
                'claims' => [
                    'username' => $username,
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

    #[Route('room/{id}/song', name: 'add_song', methods: ['POST'])]
    public function addSong(string $id, DocumentManager $dm, Request $request, TokenFactory $tokenFactory): Response
    {
        $jwt = $request->headers->get('Authorization');

        if (!$jwt) {
            throw new UnauthorizedHttpException('Bearer', 'JWT Token not found');
        }

        if (!str_starts_with($jwt, 'Bearer ') && !str_starts_with($jwt, 'bearer ')) {
            throw new UnauthorizedHttpException('Bearer', "The Authorization scheme named: 'Bearer' was not found");
        }

        $jwt = explode(' ', $jwt)[1];

        if (!$tokenFactory->validateToken($jwt)) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid JWT Token');
        }

        $form = $this->createForm(SongType::class);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            throw new FormHttpException($form);
        }

        /** @var Room|null $room */
        $room = $dm->getRepository(Room::class)->findOneBy(['id' => str_replace('-', '', $id)]);
        if (!$room) {
            throw new NotFoundHttpException('The room does not exist');
        }

        /** @var Plain $parsedToken */
        $parsedToken = $tokenFactory->parseToken($jwt);
        $claims = $parsedToken->claims()->all();

        if (!isset($claims['roomId']) || !isset($claims['username'])) {
            throw new AccessDeniedHttpException('Unexpected JWT token payload');
        }

        if ($claims['roomId'] != $room->getId()) {
            throw new AccessDeniedHttpException('JWT Token does not belong to this room');
        }

        $guestIsDisconnected = true;
        foreach ($room->getGuests() as $guest) {
            if ($guest->getUsername() == $claims['username']) {
                $guestIsDisconnected = false;
                if (Guest::ROLE_GUEST == $guest->getRole()) {
                    throw new AccessDeniedHttpException("You don't have the permission to add song to this room");
                }
            }
        }

        if ($guestIsDisconnected) {
            throw new AccessDeniedHttpException('Guest is disconnected');
        }

        // check if guest has role
        $song = (new Song())->setUrl($request->request->get('url'));

        $room->addSong($song);
        $dm->flush();

        return $this->json($song);
    }
}
