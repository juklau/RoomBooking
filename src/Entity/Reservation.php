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
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTime $reservationStart = null;

    #[ORM\Column]
    private ?\DateTime $reservationEnd = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

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
}
