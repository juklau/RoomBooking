<?php

namespace App\Entity;

use App\Repository\AdministratorRepository;
use Doctrine\ORM\Mapping as ORM;

//pour les requêtes Doctrine utiliser le repository de l'administrateur
//Administrator est une classe qui représente un administrateur dans l'application, 
//      avec une relation one-to-one avec la classe User
#[ORM\Entity(repositoryClass: AdministratorRepository::class)]
class Administrator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; //=> n'existe pas encore tant que l'entité n'est pas persistée en BDD.


    // OneToOne — un Administrator correspond à exactement un User, et un User ne peut 
    //                  être admin qu'une seule fois
    // inversedBy: 'administrator' => c'est User qui possède l'autre 
    //                  côté de la relation, avec une propriété $administrator
    // cascade: ['persist', 'remove'] — si jepersiste ou supprime un 
    //                  Administrator, l'opération se propage automatiquement au User lié
    #[ORM\OneToOne(inversedBy: 'administrator', cascade: ['persist', 'remove'])]

    // un Administrator ne peut pas exister sans User, c'est une contrainte BDD
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

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
}
