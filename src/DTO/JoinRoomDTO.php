<?php

namespace App\DTO;

use App\Entity\Guest;
use App\Entity\Room;

class JoinRoomDTO
{
    private Guest $guest;

    private Room $room;

    public function getGuest(): Guest
    {
        return $this->guest;
    }

    public function setGuest(Guest $guest): self
    {
        $this->guest = $guest;

        return $this;
    }

    public function getRoom(): Room
    {
        return $this->room;
    }

    public function setRoom(Room $room): self
    {
        $this->room = $room;

        return $this;
    }
}
