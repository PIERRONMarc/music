<?php

namespace App\Application\Command;

interface CommandBusInterface
{
    /**
     * Handle the given command and optionally returns a value.
     */
    public function handle(CommandInterface $command): mixed;
}
