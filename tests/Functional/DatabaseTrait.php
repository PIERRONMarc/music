<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;

trait DatabaseTrait
{
    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}
