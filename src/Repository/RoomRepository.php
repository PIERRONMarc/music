<?php

namespace App\Repository;

use App\Document\Room;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Exception;
use MongoDB\BSON\ObjectId;
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
                                        'input' => '$$guest.name',
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

    /**
     * @throws MongoDBException
     */
    public function deleteSong(string $roomId, string $songId): ?Room
    {
        try {
            $songId = new ObjectId($songId);
        } catch (Exception $exception) {
            return null;
        }

        return $this->createQueryBuilder()
            ->findAndUpdate()
            ->returnNew()
            ->field('id')->equals($roomId)
            ->field('songs')->pull(['_id' => $songId])
            ->getQuery()
            ->execute()
        ;
    }
}
