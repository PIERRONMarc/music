<?php

namespace App\Controller;

use App\Document\Guest as GuestDocument;
use App\Document\Room as RoomDocument;
use App\DTO\JoinRoomDTO;
use App\Entity\Guest;
use App\Entity\Room;
use App\Exception\FormHttpException;
use App\Form\HandleGuestRoleType;
use App\Mercure\Message\CreateRoomMessage;
use App\Mercure\Message\GuestJoinMessage;
use App\Mercure\Message\UpdateGuestMessage;
use App\Service\Jwt\TokenFactory;
use App\Service\Jwt\TokenValidator;
use App\Service\RandomNameGenerator\GuestName\RandomGuestNameGenerator;
use App\Service\RandomNameGenerator\RoomName\RandomRoomNameGenerator;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Annotation\Route;

class RoomController extends AbstractController
{
    #[Route('/room', name: 'create_room', methods: ['POST'])]
    public function index(
        DocumentManager $documentManager,
        EntityManagerInterface $entityManager,
        RandomRoomNameGenerator $randomRoomNameGenerator,
        RandomGuestNameGenerator $randomGuestNameGenerator,
        TokenFactory $tokenFactory,
        HubInterface $hub,
    ): Response {
        $guestName = $randomGuestNameGenerator->getName();
        $hostDocument = (new GuestDocument())
            ->setName($guestName)
            ->setRole(GuestDocument::ROLE_ADMIN)
        ;
        $host = (new Guest())
            ->setName($guestName)
            ->setRole(Guest::ROLE_ADMIN)
        ;
        $roomDocument = (new RoomDocument())
            ->setHost($hostDocument)
            ->addGuest($hostDocument)
            ->setName($randomRoomNameGenerator->getName())
        ;
        $room = (new Room())
            ->setHost($host)
            ->addGuest($host)
            ->setName($randomRoomNameGenerator->getName())
        ;

        $documentManager->persist($roomDocument);
        $entityManager->persist($room);
        $documentManager->flush();
        $entityManager->flush();

        $hostDocument->setToken($tokenFactory->createToken([
            'claims' => [
                'guestName' => $guestName,
                'roomId' => $roomDocument->getId(),
            ],
        ])->toString());
        $roomDocument->setHost($hostDocument);

        $message = new CreateRoomMessage($roomDocument->getId(), $roomDocument->getName());
        $hub->publish($message->buildUpdate());

        return $this->json($roomDocument, Response::HTTP_CREATED);
    }

    #[Route('/room', name: 'get_all_room', methods: ['GET', 'OPTIONS'])]
    public function getAllRooms(DocumentManager $dm, Request $request): Response
    {
        $page = 0 === $request->query->getInt('page', 1) ? 1 : $request->query->getInt('page', 1);
        $offset = ($page - 1) * 30;
        $rooms = $dm->getRepository(RoomDocument::class)->findBy([], [], 30, $offset);

        return $this->json($rooms, Response::HTTP_OK, [], ['groups' => 'get_all_room']);
    }

    #[Route('/join/{id}', name: 'join_room', methods: ['GET', 'OPTIONS'])]
    public function joinRoom(
        string $id,
        DocumentManager $dm,
        RandomGuestNameGenerator $randomGuestNameGenerator,
        TokenFactory $tokenFactory,
        HubInterface $hub
    ): Response {
        $room = $dm->getRepository(RoomDocument::class)->findOneBy(['id' => str_replace('-', '', $id)]);

        if (!$room) {
            throw new NotFoundHttpException('The room '.$id.' does not exist.');
        }

        $guestName = $randomGuestNameGenerator->getNameForRoom($room->getId());
        $guest = (new GuestDocument())
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

        $message = new GuestJoinMessage(
            $room->getId(),
            $guest->getName(),
            $guest->getRole()
        );
        $hub->publish($message->buildUpdate());

        return $this->json((new JoinRoomDTO())
            ->setGuest($guest)
            ->setRoom($room)
        );
    }

    #[Route('/room/{roomId}/grant-role/{guestName}', name: 'grant_role_on_guest', methods: ['PATCH'])]
    public function grantRoleOnGuest(
        string $roomId,
        string $guestName,
        DocumentManager $documentManager,
        Request $request,
        TokenValidator $tokenValidator,
        HubInterface $hub
    ): Response {
        $jwt = $tokenValidator->validateAuthorizationHeaderAndGetToken($request->headers->get('Authorization'));

        $form = $this->createForm(HandleGuestRoleType::class);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            throw new FormHttpException($form);
        }

        $role = $request->request->get('role');
        $payload = $tokenValidator->validateAndGetPayload($jwt, ['roomId', 'guestName']);

        $room = $documentManager->getRepository(RoomDocument::class)->findOneBy(['id' => $roomId]);
        if (!$room) {
            throw new NotFoundHttpException('The room does not exist');
        }

        if ($payload['roomId'] != $room->getId()) {
            throw new AccessDeniedHttpException('JWT Token does not belong to this room');
        }

        if ($payload['guestName'] !== $room->getHost()->getName()) {
            throw new AccessDeniedHttpException("You don't have the permission to grant roles in this room");
        }

        if ($guestName === $room->getHost()->getName()) {
            throw new BadRequestHttpException("You can't update the role of the host");
        }

        $guestIsDisconnected = true;
        foreach ($room->getGuests() as $guest) {
            if ($guest->getName() == $guestName) {
                $guest->setRole($role);
                $guestIsDisconnected = false;
            }
        }

        if ($guestIsDisconnected) {
            throw new NotFoundHttpException('Guest is not found');
        }

        $message = new UpdateGuestMessage(
            $room->getId(),
            $guestName,
            $role
        );
        $hub->publish($message->buildUpdate());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
