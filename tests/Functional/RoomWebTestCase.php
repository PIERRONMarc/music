<?php

namespace App\Tests\Functional;

use App\Document\Guest as GuestDocument;
use App\Document\Room as RoomDocument;
use App\Document\Song as SongDocument;
use App\Entity\Guest;
use App\Entity\Room;
use App\Entity\Song;
use App\Service\Jwt\TokenFactory;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class RoomWebTestCase extends WebTestCase
{
    use DatabaseTrait;

    protected function createRoomDocument(): RoomDocument
    {
        $tokenFactory = static::getContainer()->get(TokenFactory::class);
        $host = (new GuestDocument())
            ->setName('Angry ape')
            ->setRole(Guest::ROLE_ADMIN)
        ;
        $room = (new RoomDocument())
            ->setHost($host)
            ->addGuest($host)
            ->setName('Red rocks')
        ;
        $this->getDocumentManager()->persist($room);
        $this->getDocumentManager()->flush();

        $host->setToken($tokenFactory->createToken([
            'claims' => [
                'guestName' => 'Angry ape',
                'roomId' => $room->getId(),
            ],
        ])->toString());
        $room->setHost($host);

        return $room;
    }

    protected function createRoom(): Room
    {
        $tokenFactory = static::getContainer()->get(TokenFactory::class);
        $host = (new Guest())
            ->setName('Angry ape')
            ->setRole(GuestDocument::ROLE_ADMIN)
        ;
        $room = (new Room())
            ->setHost($host)
            ->addGuest($host)
            ->setName('Red rocks')
        ;
        $this->getEntityManager()->persist($room);
        $this->getEntityManager()->flush();

        $host->setToken($tokenFactory->createToken([
            'claims' => [
                'guestName' => 'Angry ape',
                'roomId' => $room->getId(),
            ],
        ])->toString());
        $room->setHost($host);

        return $room;
    }

    protected function joinRoom(Room $room): Guest
    {
        $tokenFactory = static::getContainer()->get(TokenFactory::class);
        $room = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());

        $guest = (new Guest())
            ->setName('Grumpy cat')
            ->setToken($tokenFactory->createToken([
                'claims' => [
                    'guestName' => 'Grumpy cat',
                    'roomId' => $room->getId(),
                ],
            ])->toString())
            ->setRole(Guest::ROLE_GUEST)
        ;
        $room->addGuest($guest);

        $this->getEntityManager()->persist($room);
        $this->getEntityManager()->flush();

        return $guest;
    }

    /**
     * @param mixed[] $options
     *
     * @throws MongoDBException
     */
    protected function addSongDocument(RoomDocument $room, bool $isCurrentSong = false, array $options = []): SongDocument
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $options = $resolver->resolve($options);

        $song = (new SongDocument())->setUrl($options['url']);
        $room = $this->getDocumentManager()->getRepository(RoomDocument::class)->findOneBy(['id' => $room->getId()]);

        if ($isCurrentSong) {
            $room->setCurrentSong($song);
        } else {
            $room->addSong($song);
        }

        $this->getDocumentManager()->persist($room);
        $this->getDocumentManager()->flush();

        return $song;
    }

    /**
     * @param mixed[] $options
     */
    protected function addSong(Room $room, bool $isCurrentSong = false, array $options = []): Song
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $options = $resolver->resolve($options);

        $song = (new Song())
            ->setUrl($options['url'])
            ->setAuthor('author')
            ->setTitle('title')
            ->setLengthInSeconds(100)
        ;
        $room = $this->getEntityManager()->getRepository(Room::class)->findOneById($room->getId()->toRfc4122());

        if ($isCurrentSong) {
            $room->setCurrentSong($song);
        } else {
            $room->addSong($song);
        }

        $this->getEntityManager()->persist($room);
        $this->getEntityManager()->flush();

        return $song;
    }

    /**
     * @return Room[]
     */
    protected function storeRooms(int $numberOfRooms): array
    {
        $rooms = [];

        $entityManager = $this->getEntityManager();
        for ($i = 0; $i < $numberOfRooms; ++$i) {
            $host = (new Guest())
                ->setName('John')
                ->setRole(Guest::ROLE_ADMIN)
            ;
            $room = (new Room())
                ->setName((string) $i)
                ->setHost($host)
            ;
            $entityManager->persist($room);
            $rooms[] = $room;
        }
        $entityManager->flush();

        return $rooms;
    }
}
