<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
class Classe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null; //=> n'existe pas encore tant que l'entité n'est pas persistée en BDD.

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    /**
     * @var Collection<int, Coordinator>
     * Coordinator qui possède la relation (le côté qui a inversedBy). 
     * Classe est le côté inverse, donc c'est Coordinator qui gère la table de jointure en BDD
     */
    #[ORM\ManyToMany(targetEntity: Coordinator::class, mappedBy: 'classes')]
    private Collection $coordinators;

    /**
     * @var Collection<int, Student>
     * La clé étrangère classe_id se trouve dans la table student, pas dans classe
     */
    #[ORM\OneToMany(targetEntity: Student::class, mappedBy: 'classe')]
    private Collection $students;

    public function __construct()
    {
        $this->coordinators = new ArrayCollection();
        $this->students = new ArrayCollection();
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

    /**
     * @return Collection<int, Coordinator>
     */
    public function getCoordinators(): Collection
    {
        return $this->coordinators;
    }

    public function addCoordinator(Coordinator $coordinator): static
    {
        if (!$this->coordinators->contains($coordinator)) {
            $this->coordinators->add($coordinator);

            // Coordinator qui possède la relation, il faut 
            // le notifier sinon Doctrine ne persiste pas le lien en BDD.
            $coordinator->addClasse($this);     // => synchronise l'autre côté // ← appelle le côté propriétaire
        }

        return $this;
    }

    public function removeCoordinator(Coordinator $coordinator): static
    {
        if ($this->coordinators->removeElement($coordinator)) {
            $coordinator->removeClass($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): static
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setClasse($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): static
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getClasse() === $this) {
                $student->setClasse(null);
            }
        }

        return $this;
    }
}
