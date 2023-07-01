<?php

namespace App\Controller;

use App\Document\Guest;
use App\Document\Room;
use App\Document\Song;
use App\Exception\FormHttpException;
use App\Form\SongType;
use App\Form\UpdateCurrentSongType;
use App\Mercure\Message\AddSongMessage;
use App\Mercure\Message\DeleteSongMessage;
use App\Mercure\Message\UpdateCurrentSongMessage;
use App\Repository\RoomRepository;
use App\Service\Jwt\TokenValidator;
use App\Service\Room\RoomAuthorization;
use App\Service\SongProvider\Exception\SongNotFoundException;
use App\Service\SongProvider\Youtube\YoutubeClient;
use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use http\Exception\RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Annotation\Route;

class SongController extends AbstractController
{
    #[Route('room/{id}/song', name: 'add_song', methods: ['POST'])]
    public function addSong(
        string $id,
        DocumentManager $dm,
        Request $request,
        TokenValidator $tokenValidator,
        RoomAuthorization $roomAuthorization,
        HubInterface $hub,
        YoutubeClient $youtubeClient
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

        if (!$roomAuthorization->guestIsGranted(
            Guest::ROLE_ADMIN,
            $payload['guestName'],
            $room->getGuests()->toArray()
        )) {
            throw new AccessDeniedHttpException("You don't have the permission to add song to this room");
        }

        $url = $request->request->get('url');
        parse_str(parse_url($url, \PHP_URL_QUERY), $query);
        $videoId = $query['v'] ?? null;

        try {
            $songDTO = $youtubeClient->getSong($videoId);
        } catch (SongNotFoundException $exception) {
            throw new NotFoundHttpException('Song not found');
        } catch (Exception $exception) {
            throw new RuntimeException('Searching the song failed');
        }

        $song = (new Song())
            ->setUrl($url)
            ->setTitle($songDTO->title)
            ->setAuthor($songDTO->author)
            ->setLengthInSeconds($songDTO->lengthInSeconds);
        if (null === $room->getCurrentSong()) {
            $room->setCurrentSong($song);
        } else {
            $room->addSong($song);
        }

        $dm->flush();

        $message = new AddSongMessage(
            $room->getId(),
            $song->getId(),
            $song->getUrl()
        );
        $hub->publish($message->buildUpdate());

        return $this->json($song, Response::HTTP_CREATED);
    }

    #[Route('room/{roomId}/song/{songId}', name: 'delete_song', methods: ['DELETE'])]
    public function deleteSong(
        DocumentManager $documentManager,
        string $roomId,
        string $songId,
        TokenValidator $tokenValidator,
        Request $request,
        RoomAuthorization $roomAuthorization,
        HubInterface $hub
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

        if ($roomAuthorization->guestIsGranted(
            Guest::ROLE_GUEST,
            $payload['guestName'],
            $room->getGuests()->toArray()
        )) {
            throw new AccessDeniedHttpException("You don't have the permission to delete song in this room");
        }

        /** @var RoomRepository $roomRepository */
        $roomRepository = $documentManager->getRepository(Room::class);
        $roomRepository->deleteSong($roomId, $songId);

        $message = new DeleteSongMessage(
            $room->getId(),
            $songId
        );
        $hub->publish($message->buildUpdate());

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }

    #[Route('room/{roomId}/current-song', name: 'update_current_song', methods: ['PATCH'])]
    public function updateCurrentSong(
        string $roomId,
        TokenValidator $tokenValidator,
        Request $request,
        DocumentManager $documentManager,
        RoomAuthorization $roomAuthorization,
        HubInterface $hub,
    ): Response {
        $jwt = $tokenValidator->validateAuthorizationHeaderAndGetToken($request->headers->get('Authorization'));

        $form = $this->createForm(UpdateCurrentSongType::class);
        $form->submit($request->request->all());

        if (!$form->isValid()) {
            throw new FormHttpException($form);
        }

        $payload = $tokenValidator->validateAndGetPayload($jwt, ['roomId', 'guestName']);

        /** @var Room|null $room */
        $room = $documentManager->getRepository(Room::class)->findOneBy(['id' => str_replace('-', '', $roomId)]);
        if (!$room) {
            throw new NotFoundHttpException('The room does not exist');
        }

        if ($payload['roomId'] != $room->getId()) {
            throw new AccessDeniedHttpException('JWT Token does not belong to this room');
        }

        if ($roomAuthorization->guestIsGranted(
            Guest::ROLE_GUEST,
            $payload['guestName'],
            $room->getGuests()->toArray()
        )) {
            throw new AccessDeniedHttpException("You don't have the permission to update the current song in this room");
        }

        if (!$room->getCurrentSong()) {
            throw new NotFoundHttpException('There is no current song');
        }

        $room->getCurrentSong()->setIsPaused($request->request->get('isPaused'));

        $message = new UpdateCurrentSongMessage(
            $room->getId(),
            $room->getCurrentSong()->getUrl(),
            $room->getCurrentSong()->getIsPaused()
        );

        $hub->publish($message->buildUpdate());

        return $this->json($room->getCurrentSong());
    }

    #[Route('/room/{roomId}/next-song', name: 'go_to_next_song', methods: 'GET')]
    public function goToNextSong(
        string $roomId,
        DocumentManager $documentManager,
        TokenValidator $tokenValidator,
        Request $request,
        RoomAuthorization $roomAuthorization,
        HubInterface $hub
    ): Response {
        $jwt = $tokenValidator->validateAuthorizationHeaderAndGetToken($request->headers->get('Authorization'));
        $payload = $tokenValidator->validateAndGetPayload($jwt, ['roomId', 'guestName']);

        $room = $documentManager->getRepository(Room::class)->findOneBy(['id' => $roomId]);
        if (!$room) {
            throw new NotFoundHttpException('The room does not exist');
        }

        if ($payload['roomId'] != $room->getId()) {
            throw new AccessDeniedHttpException('JWT Token does not belong to this room');
        }

        if ($roomAuthorization->guestIsGranted(
            Guest::ROLE_GUEST,
            $payload['guestName'],
            $room->getGuests()->toArray()
        )) {
            throw new AccessDeniedHttpException("You don't have the permission to go to the next song in this room");
        }

        if ($room->getSongs()->isEmpty()) {
            throw new NotFoundHttpException('There is no song to go');
        }

        $room->setCurrentSong($room->getSongs()->first());
        $room->removeSong($room->getSongs()->first());

        $documentManager->persist($room);
        $documentManager->flush();

        $message = new UpdateCurrentSongMessage(
            $room->getId(),
            $room->getCurrentSong()->getUrl(),
            $room->getCurrentSong()->getIsPaused()
        );

        $hub->publish($message->buildUpdate());

        return $this->json($room->getCurrentSong());
    }
}
