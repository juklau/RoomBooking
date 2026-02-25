<?php

namespace App\Entity;

use App\Repository\EquipmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: EquipmentRepository::class)]
class Equipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    // #[ORM\ManyToOne(inversedBy: 'equipments')]
    // #[ORM\JoinColumn(nullable: false)]
    // private ?Room $room = null;

    #[ORM\ManyToMany(targetEntity: Room::class, mappedBy: 'equipments')]
    private Collection $rooms;

    public function __construct()
    {
        $this->rooms = new ArrayCollection();  //=> initialisation de la collection
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Room $room): static
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms->add($room);
        }
        return $this;
    }

    public function removeEquipment(Room $room): static
    {
        $this->rooms->removeElement($room);
        return $this;
    }

    // public function getRoom(): ?Room
    // {
    //     return $this->room;
    // }

    // public function setRoom(?Room $room): static
    // {
    //     $this->room = $room;

    //     return $this;
    // }
}
