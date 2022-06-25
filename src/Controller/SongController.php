<?php

namespace App\Controller;

use App\Document\Guest;
use App\Document\Room;
use App\Document\Song;
use App\Exception\FormHttpException;
use App\Form\SongType;
use App\Repository\RoomRepository;
use App\Service\Jwt\TokenValidator;
use App\Service\Room\RoomAuthorization;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SongController extends AbstractController
{
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
        if (null === $room->getCurrentSong()) {
            $room->setCurrentSong($song);
        } else {
            $room->addSong($song);
        }

        $dm->flush();

        return $this->json($song, Response::HTTP_CREATED);
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
