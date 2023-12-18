<?php

namespace App\Repository;

use App\Entity\Room;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Room>
 *
 * @method Room|null find($id, $lockMode = null, $lockVersion = null)
 * @method Room|null findOneBy(array $criteria, array $orderBy = null)
 * @method Room[]    findAll()
 * @method Room[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Room::class);
    }

    public function findOneById(string $id): ?Room
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Count the number of rooms that (partially or not) match the given room name.
     */
    public function countRoomWithNameLike(string $name): int
    {
        return $this->createQueryBuilder('room')
            ->select('count(room.id)')
            ->where('room.name like :name')
            ->setParameter('name', $name.'%')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Count the number of guests that (partially or not) match the given guest name for a given room.
     */
    public function countGuestWithNameLike(string $name, string $roomId): int
    {
        return $this->createQueryBuilder('room')
            ->select('count(guest.id)')
            ->join('room.guests', 'guest')
            ->where('room.id = :roomId')
            ->andWhere('guest.name like :name')
            ->setParameters([
                'roomId' => $roomId,
                'name' => $name.'%',
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
