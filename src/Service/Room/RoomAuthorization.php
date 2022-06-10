<?php

namespace App\Service\Room;

use App\Document\Guest;

class RoomAuthorization
{
    /**
     * @param Guest[] $guests
     */
    public function guestIsGranted(string $role, string $guestUsername, array $guests): bool
    {
        foreach ($guests as $guest) {
            if ($guest->getUsername() === $guestUsername) {
                if ($role == $guest->getRole()) {
                    return true;
                }
            }
        }

        return false;
    }
}
