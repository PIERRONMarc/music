<?php

namespace App\Service\Room;

use App\Entity\Guest;

class RoomAuthorization
{
    /**
     * @param Guest[] $guests
     */
    public function guestIsGranted(string $role, string $guestName, array $guests): bool
    {
        foreach ($guests as $guest) {
            if ($guest->getName() === $guestName) {
                if ($role == $guest->getRole()) {
                    return true;
                }
            }
        }

        return false;
    }
}
