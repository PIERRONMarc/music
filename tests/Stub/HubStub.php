<?php

namespace App\Tests\Stub;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Jwt\TokenProviderInterface;
use Symfony\Component\Mercure\Update;

class HubStub implements HubInterface
{
    public function publish(Update $update): string
    {
        return 'id';
    }

    public function getUrl(): string
    {
        return '';
    }

    public function getPublicUrl(): string
    {
        return '';
    }

    public function getProvider(): TokenProviderInterface
    {
        return new StaticTokenProvider('1');
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return null;
    }
}
