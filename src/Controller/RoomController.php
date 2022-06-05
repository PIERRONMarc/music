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
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
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
            throw new NotFoundHttpException('The room '.$id.' does not exist.');
        }

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

        if (!$tokenFactory->validateToken((new Parser(new JoseEncoder()))->parse($jwt))) {
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

        if ($jwt != $room->getToken()) {
            throw new AccessDeniedHttpException('JWT Token does not belong to this room');
        }
        $song = (new Song())->setUrl($request->request->get('url'));

        $room->addSong($song);
        $dm->flush();

        return $this->json($song);
    }
}
