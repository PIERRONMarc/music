<?php

namespace App\Repository;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use MongoDB\BSON\Regex;

class RoomRepository extends DocumentRepository
{
    /**
     * Count the number of rooms that match (partially or not) the given room name.
     *
     * @throws MongoDBException
     */
    public function countRoomWithNameLike(string $name): int
    {
        return $this->createQueryBuilder()
            ->field('name')
            ->equals(new Regex('^'.$name.'.*'))
            ->count()
            ->getQuery()
            ->execute()
        ;
    }
}
