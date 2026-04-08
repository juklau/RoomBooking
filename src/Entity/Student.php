<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;  //=> n'existe pas encore tant que l'entité n'est pas persistée en BDD
                              //persister= maradni


    // un étudiant est forcément lié à un User, suppression en cascade.
    #[ORM\OneToOne(inversedBy: 'student', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // plusieurs étudiants dans une classe, mais un étudiant dans une seule classe
    #[ORM\ManyToOne(inversedBy: 'students')]

    //Un étudiant peut exister sans être rattaché à une classe.
    #[ORM\JoinColumn(nullable: true)]  //=> nullable
    private ?Classe $classe = null;

    public function getId(): ?int
    {
        return $this->id; 
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getClasse(): ?Classe
    {
        return $this->classe;
    }

    public function setClasse(?Classe $classe): static
    {
        $this->classe = $classe;

        return $this;
    }
}
