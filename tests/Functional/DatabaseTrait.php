<?php

namespace App\Tests\Functional;

use Doctrine\ODM\MongoDB\DocumentManager;

trait DatabaseTrait
{
    protected function getDocumentManager(): DocumentManager
    {
        return static::getContainer()->get('doctrine_mongodb.odm.document_manager');
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
