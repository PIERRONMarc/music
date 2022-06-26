<?php

namespace App\Tests;

use App\Document\Guest;
use App\Document\Room;
use App\Document\Song;
use App\Service\Jwt\TokenFactory;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class RoomWebTestCase extends WebTestCase
{
    use DatabaseTrait;

    protected function createRoom(): Room
    {
        $tokenFactory = static::getContainer()->get(TokenFactory::class);
        $host = (new Guest())
            ->setName('Angry ape')
            ->setRole(Guest::ROLE_ADMIN)
        ;
        $room = (new Room())
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

    protected function joinRoom(Room $room): Guest
    {
        $tokenFactory = static::getContainer()->get(TokenFactory::class);
        $room = $this->getDocumentManager()->getRepository(Room::class)->findOneBy(['id' => $room->getId()]);

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

        $this->getDocumentManager()->persist($room);
        $this->getDocumentManager()->flush();

        return $guest;
    }

    /**
     * @param mixed[] $options
     *
     * @throws MongoDBException
     */
    protected function addSong(Room $room, bool $isCurrentSong = false, array $options = []): Song
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);
        $options = $resolver->resolve($options);

        $song = (new Song())->setUrl($options['url']);
        $room = $this->getDocumentManager()->getRepository(Room::class)->findOneBy(['id' => $room->getId()]);

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
     * @return mixed[]
     *
     * @throws MongoDBException
     */
    protected function storeRooms(int $numberOfRooms): array
    {
        $rooms = [];

        $dm = $this->getDocumentManager();
        for ($i = 0; $i < $numberOfRooms; ++$i) {
            $room = (new Room())->setName((string) $i);
            $dm->persist($room);
            $rooms[] = $room;
        }
        $dm->flush();

        return $rooms;
    }
}
