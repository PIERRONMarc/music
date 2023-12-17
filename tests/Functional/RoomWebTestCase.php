<?php

namespace App\Tests\Functional;

use App\Entity\Guest;
use App\Entity\Room;
use App\Entity\Song;
use App\Service\Jwt\TokenFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class RoomWebTestCase extends WebTestCase
{
    use DatabaseTrait;

    protected function createRoom(bool $withCurrentSong = false): Room
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

        if ($withCurrentSong) {
            $song = (new Song())
                ->setTitle('title')
                ->setAuthor('author')
                ->setLengthInSeconds(100)
                ->setUrl('url')
            ;
            $room->setCurrentSong($song);
        }

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
