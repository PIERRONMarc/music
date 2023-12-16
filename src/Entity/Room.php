<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private Guest $host;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Song $currentSong = null;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Song::class, orphanRemoval: true)]
    private Collection $songs;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Guest::class, orphanRemoval: true)]
    private Collection $guests;

    public function __construct()
    {
        $this->songs = new ArrayCollection();
        $this->guests = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getHost(): Guest
    {
        return $this->host;
    }

    public function setHost(Guest $host): static
    {
        $this->host = $host;

        return $this;
    }

    public function getCurrentSong(): ?Song
    {
        return $this->currentSong;
    }

    public function setCurrentSong(?Song $currentSong): static
    {
        $this->currentSong = $currentSong;

        return $this;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): static
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
            $song->setRoom($this);
        }

        return $this;
    }

    public function removeSong(Song $song): static
    {
        if ($this->songs->removeElement($song)) {
            // set the owning side to null (unless already changed)
            if ($song->getRoom() === $this) {
                $song->setRoom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Guest>
     */
    public function getGuests(): Collection
    {
        return $this->guests;
    }

    public function addGuest(Guest $guest): static
    {
        if (!$this->guests->contains($guest)) {
            $this->guests->add($guest);
            $guest->setRoom($this);
        }

        return $this;
    }

    public function removeGuest(Guest $guest): static
    {
        if ($this->guests->removeElement($guest)) {
            // set the owning side to null (unless already changed)
            if ($guest->getRoom() === $this) {
                $guest->setRoom(null);
            }
        }

        return $this;
    }
}
