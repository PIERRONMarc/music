<?php

namespace App\Repository;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use MongoDB\BSON\Regex;
use MongoDB\Driver\Cursor;

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

    /**
     * Count the number of rooms that match (partially or not) the given room name.
     */
    public function countGuestWithNameLike(string $name, string $roomId): int
    {
        /** @var Cursor $cursor */
        $cursor = $this->dm->getClient()->selectCollection($_ENV['MONGODB_DB'], 'rooms')->aggregate([
            [
                '$match' => [
                    '_id' => $roomId,
                    'guests' => [
                        '$elemMatch' => [
                            '$exists' => true,
                        ],
                    ],
                ],
            ],
            [
                '$project' => [
                    'count' => [
                        '$size' => [
                            '$filter' => [
                                'input' => '$guests',
                                'as' => 'guest',
                                'cond' => [
                                    '$regexMatch' => [
                                        'input' => '$$guest.username',
                                        'regex' => '^'.$name,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $cursor->toArray();

        if ($result) {
            return $result[0]['count'];
        }

        return 0;
    }
}
