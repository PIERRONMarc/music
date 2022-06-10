<?php

namespace App\Controller;

use App\Document\Guest;
use App\Document\Room;
use App\Document\Song;
use App\DTO\JoinRoomDTO;
use App\Exception\FormHttpException;
use App\Form\SongType;
use App\Repository\RoomRepository;
use App\Service\Jwt\TokenFactory;
use App\Service\Jwt\TokenValidator;
use App\Service\RandomNameGenerator\GuestName\RandomGuestNameGenerator;
use App\Service\RandomNameGenerator\RoomName\RandomRoomNameGenerator;
use App\Service\Room\RoomAuthorization;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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

    #[Route('room/{id}/song', name: 'add_song', methods: ['POST'])]
    public function addSong(
        string $id,
        DocumentManager $dm,
        Request $request,
        TokenValidator $tokenValidator,
        RoomAuthorization $roomAuthorization
    ): Response {
        $jwt = $tokenValidator->validateAuthorizationHeaderAndGetToken($request->headers->get('Authorization'));

        $form = $this->createForm(SongType::class);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            throw new FormHttpException($form);
        }

        $payload = $tokenValidator->validateAndGetPayload($jwt, ['roomId', 'guestName']);

        /** @var Room|null $room */
        $room = $dm->getRepository(Room::class)->findOneBy(['id' => str_replace('-', '', $id)]);
        if (!$room) {
            throw new NotFoundHttpException('The room does not exist');
        }

        if ($payload['roomId'] != $room->getId()) {
            throw new AccessDeniedHttpException('JWT Token does not belong to this room');
        }

        if ($roomAuthorization->guestIsGranted(Guest::ROLE_GUEST, $payload['guestName'], $room->getGuests()->toArray())) {
            throw new AccessDeniedHttpException("You don't have the permission to add song to this room");
        }

        $song = (new Song())->setUrl($request->request->get('url'));

        $room->addSong($song);
        $dm->flush();

        return $this->json($song);
    }

    #[Route('room/{roomId}/song/{songId}', name: 'delete_song', methods: ['DELETE'])]
    public function deleteSong(
        DocumentManager $documentManager,
        string $roomId,
        string $songId,
        TokenValidator $tokenValidator,
        Request $request,
        RoomAuthorization $roomAuthorization
    ): Response {
        $jwt = $tokenValidator->validateAuthorizationHeaderAndGetToken($request->headers->get('Authorization'));

        $payload = $tokenValidator->validateAndGetPayload($jwt, ['roomId', 'guestName']);

        /** @var Room|null $room */
        $room = $documentManager->getRepository(Room::class)->findOneBy(['id' => str_replace('-', '', $roomId)]);
        if (!$room) {
            throw new NotFoundHttpException('The room does not exist');
        }

        if ($payload['roomId'] != $room->getId()) {
            throw new AccessDeniedHttpException('JWT Token does not belong to this room');
        }

        if ($roomAuthorization->guestIsGranted(Guest::ROLE_GUEST, $payload['guestName'], $room->getGuests()->toArray())) {
            throw new AccessDeniedHttpException("You don't have the permission to add song to this room");
        }

        /** @var RoomRepository $roomRepository */
        $roomRepository = $documentManager->getRepository(Room::class);
        $roomRepository->deleteSong($roomId, $songId);

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
