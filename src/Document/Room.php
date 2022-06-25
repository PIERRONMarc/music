<?php

namespace App\Document;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @MongoDB\Document(collection="rooms", repositoryClass=RoomRepository::class)
 */
class Room
{
    /**
     * @MongoDB\Id(strategy="UUID")
     * @Groups({"get_all_room"})
     */
    private ?string $id = null;

    /**
     * @MongoDB\Field(type="string")
     * @Groups({"get_all_room"})
     */
    private string $name;

    /**
     * @MongoDB\EmbedOne(targetDocument=Guest::class)
     */
    private Guest $host;

    /**
     * @MongoDB\EmbedOne(targetDocument=Song::class)
     */
    private ?Song $currentSong = null;

    /**
     * @MongoDB\EmbedMany(targetDocument=Song::class)
     */
    private Collection $songs;

    /**
     * @MongoDB\EmbedMany(targetDocument=Guest::class)
     */
    private Collection $guests;

    public function __construct()
    {
        $this->songs = new ArrayCollection();
        $this->guests = new ArrayCollection();
    }

    public function getId(): ?string
    {
        if ($this->id) {
            return $this->id;
        }

        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setHost(Guest $host): self
    {
        $this->host = $host;

        return $this;
    }

    public function getHost(): Guest
    {
        return $this->host;
    }

    public function getCurrentSong(): ?Song
    {
        return $this->currentSong;
    }

    public function setCurrentSong(?Song $currentSong): self
    {
        $this->currentSong = $currentSong;

        return $this;
    }

    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): self
    {
        if (!$this->songs->contains($song)) {
            $this->songs[] = $song;
        }

        return $this;
    }

    public function removeSong(Song $song): self
    {
        $this->songs->removeElement($song);

        return $this;
    }

    public function getGuests(): Collection
    {
        return $this->guests;
    }

    public function addGuest(Guest $guest): self
    {
        if (!$this->guests->contains($guest)) {
            $this->guests[] = $guest;
        }

        return $this;
    }

    public function removeGuest(Guest $guest): self
    {
        $this->guests->removeElement($guest);

        return $this;
    }
}
