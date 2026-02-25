<?php

namespace App\Form;

use App\Entity\Student;
use App\Repository\StudentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddStudentToClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $classeId = $options['classe_id'];

        $builder
            ->add('student', EntityType::class, [
                'class'        => Student::class,
                'choice_label' => fn(Student $s) => $s->getUser()->getFirstname() . ' ' . $s->getUser()->getLastname() . ' (' . $s->getUser()->getEmail() . ')',
                'label'        => 'Étudiant',
                'placeholder'  => '-- Choisir un étudiant --',
                'required'     => true,
                'constraints'  => [
                    new NotBlank(message: "Veuillez sélectionner un étudiant."),
                ],

                // afficher que les étudiants qui sont dans une autre classe  ==> V2 créer étudiants sans classe
                //  fn(StudentRepository $repo) => function flèche PHP == function($repo)
                'query_builder' => fn(StudentRepository $repo) => $repo->createQueryBuilder('s')
                    ->leftJoin('s.classe', 'c')
                    ->where('s.classe IS NULL OR c.id != :classeId')        //=> ('s.classe IS NULL OR c.id != :classeId') => qui ont pas de classe ou sont dans une autre classe
                    ->setParameter('classeId', $classeId)                   //function contre injection SQL
                    ->leftJoin('s.user', 'u')                               // => pour pouvoir trier par lastname
                    ->orderBy('u.lastname', 'ASC'),
                'attr' => ['class' => 'form-select']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
        $resolver->setRequired('classe_id');
    }
}