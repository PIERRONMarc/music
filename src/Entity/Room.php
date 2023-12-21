<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    #[Groups(groups: ['get_all_room'])]
    private Uuid $id;

    #[ORM\Column(length: 255)]
    #[Groups(groups: ['get_all_room'])]
    private string $name;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private Guest $host;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], fetch: 'EAGER')]
    private ?Song $currentSong = null;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Song::class, cascade: ['persist'])]
    private Collection $songs;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Guest::class, cascade: ['persist'])]
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
        $currentSong?->setRoom($this);

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

    public function getGuest(string $guestName): ?Guest
    {
        foreach ($this->guests as $guest) {
            if ($guest->getName() === $guestName) {
                return $guest;
            }
        }

        return null;
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

    public function removeGuestByName(string $guestName): self
    {
        foreach ($this->guests as $guest) {
            if ($guest->getName() === $guestName) {
                $this->guests->removeElement($guest);

                return $this;
            }
        }

        return $this;
    }

    public function hasGuests(): bool
    {
        return $this->guests->count() > 0;
    }

    public function selectAnotherAdmin(): void
    {
        if ($this->hasGuests()) {
            $guest = $this->guests->first();
            $guest->setAdmin();
            $this->host = $guest;
        }
    }

    public function getAdmin(): Guest
    {
        foreach ($this->guests as $guest) {
            if ($guest->isAdmin()) {
                return $guest;
            }
        }

        throw new \LogicException('There is no admin in this room');
    }
}
