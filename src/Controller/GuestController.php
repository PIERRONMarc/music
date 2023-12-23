<?php

namespace App\Controller;

use App\Mercure\Message\DeleteRoomMessage;
use App\Mercure\Message\GuestLeaveMessage;
use App\Mercure\Message\UpdateGuestMessage;
use App\Repository\RoomRepository;
use App\Service\Jwt\TokenValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Annotation\Route;

class GuestController extends AbstractController
{
    #[Route('/room/leave', name: 'leave_room', methods: ['POST'])]
    public function leaveRoom(
        Request $request,
        TokenValidator $tokenValidator,
        HubInterface $hub,
        RoomRepository $roomRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $token = $request->toArray()['token'] ?? null;
        if (!$token) {
            throw new NotFoundHttpException('There is no token in the request');
        }

        $payload = $tokenValidator->validateAndGetPayload($token, ['roomId', 'guestName']);
        $roomId = $payload['roomId'] ?? null;
        if (!$roomId) {
            throw new AccessDeniedHttpException('There is no room id in the token');
        }

        $room = $roomRepository->findOneById($roomId);
        if (!$room) {
            throw new NotFoundHttpException('The room does not exist');
        }

        $guestName = $payload['guestName'];
        if (!$guestName) {
            throw new AccessDeniedHttpException('There is no guest name in the token');
        }

        $guest = $room->getGuest($guestName);
        if (!$guest) {
            throw new NotFoundHttpException('There is no guest with this name');
        }

        $room->removeGuestByName($guestName);

        if (!$room->hasGuests()) {
            if ($room->getCurrentSong()) {
                $entityManager->remove($room->getCurrentSong());
                $room->setCurrentSong(null);
            }
            $entityManager->flush();
            $entityManager->remove($room);
            $entityManager->flush();

            $message = new DeleteRoomMessage($room->getName());
            $hub->publish($message->buildUpdate());

            return new Response();
        }

        if ($guest->isAdmin()) {
            $room->selectAnotherAdmin();
            $admin = $room->getAdmin();

            $message = new UpdateGuestMessage($roomId, $admin->getName(), $admin->getRole());
            $hub->publish($message->buildUpdate());
        }

        $entityManager->flush();

        $message = new GuestLeaveMessage($roomId, $guestName);
        $hub->publish($message->buildUpdate());

        return new Response();
    }
}
