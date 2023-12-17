<?php

namespace App\Tests\Functional;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;

trait DatabaseTrait
{
    protected function getDocumentManager(): DocumentManager
    {
        return static::getContainer()->get('doctrine_mongodb.odm.document_manager');
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @throws \Exception
     */
    protected function clearDatabase(): void
    {
        $db = $this->getDocumentManager()->getClient()->selectDatabase($_ENV['MONGODB_DB']);

        foreach ($db->listCollections() as $collection) {
            $db->dropCollection($collection->getName());
        }
    }
}
