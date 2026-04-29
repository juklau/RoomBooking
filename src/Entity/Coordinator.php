<?php

namespace App\Entity;

use App\Repository\CoordinatorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CoordinatorRepository::class)]
class Coordinator
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;  //=> n'existe pas encore tant que l'entité n'est pas persistée en BDD.

    #[ORM\OneToOne(inversedBy: 'coordinator', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Classe>
     * Coordinator qui possède la relation  grâce à inversedBy 
     * (le côté propriétaire a toujours inversedBy, => lui qui contrôle la table de jointure
     * le côté inverse a mappedBy).
     */
    #[ORM\ManyToMany(targetEntity: Classe::class, inversedBy: 'coordinators')]
    private Collection $classes;

    public function __construct()
    {
        $this->classes = new ArrayCollection();
    }

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

    /**
     * @return Collection<int, Classe>
     */
    public function getClasses(): Collection
    {
        return $this->classes;
    }

    public function addClasse(Classe $class): static
    {
        if (!$this->classes->contains($class)) {
            $this->classes->add($class);
             //pas d'appel retour vers Classe <=> sinon j'aurais un boucle infinie de synchronisation entre les deux entités.
        }
        return $this;
    }

    public function removeClass(Classe $class): static
    {
        $this->classes->removeElement($class);
        return $this;
    }
}
