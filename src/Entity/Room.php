<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; //=> n'existe pas encore tant que l'entité n'est pas persistée en BDD.

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column]
    private ?int $capacity = null;

    /**
     * @var Collection<int, Equipment>
     */
    // #[ORM\OneToMany(targetEntity: Equipment::class, mappedBy: 'room', orphanRemoval: true)]
    // Room est le côté propriétaire, c'est lui qui gère la table de jointure
    #[ORM\ManyToMany(targetEntity: Equipment::class, inversedBy: 'rooms')]
    private Collection $equipments;

    /**
     * @var Collection<int, Reservation>
     * Room est le côté inverse ici — Reservation porte la clé étrangère room_id
     * suppression d'une salle, les réservations ne sont pas supprimées automatiquement 
     *          (à gérer manuellement dans le controller) !!!
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'room')]
    private Collection $reservations;

    public function __construct()
    {
        $this->equipments = new ArrayCollection();
        $this->reservations = new ArrayCollection();
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

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    /**
     * @return Collection<int, Equipment>
     */
    public function getEquipments(): Collection
    {
        return $this->equipments;
    }

    //si manytomany => plus de setRoom()
    public function addEquipment(Equipment $equipment): static
    {
        if (!$this->equipments->contains($equipment)) {
            $this->equipments->add($equipment);
            // pas de synchronisation vers Equipment
            // Room est propriétaire, ça suffit
        }
        return $this;
    }

    public function removeEquipment(Equipment $equipment): static
    {
        $this->equipments->removeElement($equipment);
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);

            //Room est côté inverse, donc elle redirige vers 
            //      Reservation (propriétaire) pour que la persistance fonctionne.
            $reservation->setRoom($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {

            // vérifie que la réservation pointe bien vers cette salle avant de mettre null 
            if ($reservation->getRoom() === $this) {
                $reservation->setRoom(null);
            }
        }
        return $this;
    }
}
