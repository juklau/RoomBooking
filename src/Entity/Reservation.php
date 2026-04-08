<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;  //=> n'existe pas encore tant que l'entité n'est pas persistée en BDD.

    #[ORM\Column]
    private ?\DateTime $reservationStart = null;

    #[ORM\Column]
    private ?\DateTime $reservationEnd = null;

    // propriétaire des deux relations → c'est elle qui porte les clés étrangères
    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    // propriétaire des deux relations → c'est elle qui porte les clés étrangères
    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 20)]
    private string $status = 'reserved';  // valeur par défaut

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReservationStart(): ?\DateTime
    {
        return $this->reservationStart;
    }

    public function setReservationStart(\DateTime $reservationStart): static
    {
        $this->reservationStart = $reservationStart;
        return $this;
    }

    public function getReservationEnd(): ?\DateTime
    {
        return $this->reservationEnd;
    }

    public function setReservationEnd(\DateTime $reservationEnd): static
    {
        $this->reservationEnd = $reservationEnd;
        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
}
