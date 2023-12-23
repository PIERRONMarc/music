<?php

namespace App\Infrastructure\Symfony\Command;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\CommandInterface;
use League\Tactician\CommandBus as TacticianCommandBus;

readonly class CommandBus implements CommandBusInterface
{
    public function __construct(
        private TacticianCommandBus $commandBus,
    ) {
    }

    public function handle(CommandInterface $command): mixed
    {
        return $this->commandBus->handle($command);
    }
}
