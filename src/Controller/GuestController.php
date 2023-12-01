<?php

namespace App\Controller;

use App\Document\Room;
use App\Mercure\Message\GuestLeaveMessage;
use App\Service\Jwt\TokenValidator;
use Doctrine\ODM\MongoDB\DocumentManager;
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
        DocumentManager $documentManager,
        TokenValidator $tokenValidator,
        HubInterface $hub,
    ): Response
    {
        $token = $request->toArray()['token'] ?? null;
        if (!$token) {
            throw new NotFoundHttpException('There is no token in the request');
        }

        $payload = $tokenValidator->validateAndGetPayload($token, ['roomId', 'guestName']);
        $roomId = $payload['roomId'] ?? null;
        if (!$roomId) {
            throw new AccessDeniedHttpException('There is no room id in the token');
        }

        $room = $documentManager->getRepository(Room::class)->findOneBy(['id' => $roomId]);
        if (!$room) {
            throw new NotFoundHttpException('The room does not exist');
        }

        $guestName = $payload['guestName'];
        if (!$guestName) {
            throw new AccessDeniedHttpException("There is no guest name in the token");
        }

        $room->removeGuest($guestName);
        $documentManager->flush();

        $message = new GuestLeaveMessage($roomId, $guestName);
        $hub->publish($message->buildUpdate());

        return new Response();
    }
}
