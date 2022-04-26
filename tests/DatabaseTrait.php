<?php

namespace App\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;

trait DatabaseTrait
{
    protected function getDocumentManager(): DocumentManager
    {
        return $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
    }

    /**
     * @throws Exception
     */
    protected function clearDatabase(): void
    {
        $db = $this->getDocumentManager()->getClient()->selectDatabase($_ENV['MONGODB_DB']);

        foreach ($db->listCollections() as $collection) {
            $db->dropCollection($collection->getName());
        }
    }
}
