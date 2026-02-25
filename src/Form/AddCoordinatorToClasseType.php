<?php

namespace App\Form;

use App\Entity\Coordinator;
use App\Repository\CoordinatorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddCoordinatorToClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $classeId = $options['classe_id'];

        $builder
            ->add('coordinator', EntityType::class, [
                'class'        => Coordinator::class,
                'choice_label' => fn(Coordinator $c) => $c->getUser()->getFirstname() . ' ' . $c->getUser()->getLastname() . ' (' . $c->getUser()->getEmail() . ')',
                'label'        => 'Coordinateur',
                'placeholder'  => '-- Choisir un coordinateur --',
                'required'     => true,
                'constraints'  => [
                    new NotBlank(message: "Veuillez sélectionner un coordinateur."),
                ],

                // afficher que les coordinateurs qui ne sont pas encore dans cette classe => sans classe ou autre classe
                //  fn(CoordinatorRepository $repo) => function flèche PHP == function($repo)
                'query_builder' => fn(CoordinatorRepository $repo) => $repo->createQueryBuilder('c')
                    ->leftJoin('c.user', 'u')                       //il faut NOT EXISTS si ManyToMany
                    ->where('NOT EXISTS (
                        SELECT 1 FROM App\Entity\Classe cl
                        JOIN cl.coordinators cc
                        WHERE cc.id = c.id AND cl.id = :classeId
                    )')      
                    ->setParameter('classeId', $classeId)           //function contre injection SQL
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