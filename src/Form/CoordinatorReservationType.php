<?php

namespace App\Form;

use App\Entity\Coordinator;
use App\Entity\Room;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;


class CoordinatorReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        /** @var Coordinator|null $coordinator */
        $coordinator = $options['coordinator'];

        // Générer les créneaux de 08:00 à 20:00 par tranches de 30 min
            // ['08:00' => '08:00', '08:30' => '08:30', ..., '20:00' => '20:00']
        $timeSlots = [];
        for ($hour = 8; $hour <= 20; $hour++) {
            foreach ([0, 30] as $min) {

                if ($hour === 20 && $min === 30) break;             // => pas de 20:30

                $label = sprintf('%02d:%02d', $hour, $min);
                $timeSlots[$label] = $label;
            }
        }

        //collecter les Ids des étudiants de toutes les classes => pour le filtre EntityType
        $studentUserIds = [];

        if($coordinator){
            foreach($coordinator->getClasses() as $classe){
                foreach($classe->getStudents() as $student){
                    $studentUserIds [] = $student->getUser()->getId();
                }
            }
            $studentUserIds = array_unique($studentUserIds);
        }

        $builder
            ->add('room', EntityType::class, [
                'class'        => Room::class,
                'choice_label' => fn(Room $r) => $r->getName() . ' (' . $r->getCapacity() . 'places)',
                'label'        => 'Salle',
                'placeholder'  => '-- Choisir une salle --',
                'required'     => true,
                'constraints'  => [
                    new NotBlank(message: 'Veuillez choisir une salle.'
                )],
                'data'         => $options['preselected_room'],
                'attr'         => [
                    'class' => 'form-select'
                ],
            ])
            ->add('date', DateType::class, [
                'label'       => 'Date',
                'widget'      => 'single_text',                      // <input type="date">
                'required'    => true,
                'constraints' => [
                    new NotBlank(message: 'La date est obligatoire.')
                ],
                'attr'        => [
                    'class' => 'form-control',
                    'min'   => (new \DateTime('tomorrow'))->format('Y-m-d'), // interdit le passé
                ],
            ])
            ->add('startTime', ChoiceType::class, [
                'label'       => 'Heure de début',
                'choices'     => $timeSlots,
                'placeholder' => '-- Heure de début --',
                'required'    => true,
                'constraints' => [
                    new NotBlank(message: "L'heure de début est obligatoire.")
                ],
                'attr'        => [
                    'class' => 'form-select'
                ],
            ])
            ->add('endTime', ChoiceType::class, [
                'label'       => 'Heure de fin',
                'choices'     => $timeSlots,
                'placeholder' => '-- Heure de fin --',
                'required'    => true,
                'constraints' => [
                    new NotBlank(message: "L'heure de fin est obligatoire.")
                ],
                'attr'        => [
                    'class' => 'form-select'
                ],
            ])
            ->add('beneficiary', EntityType::class, [
                'class'         => User::class,
                'choice_label'  => fn(User $u) => $u->getFirstname() . ' ' . $u->getLastname() . ' (' . $u->getEmail() . ')',
                'label'         => 'Réserver pour',
                'placeholder'   => '-- Pour moi même --',
                'required'      => false,                   //s'il est vide => admin connecté

                //filtrer uniquement les étudiants de ses classes
                'query_builder' => fn(UserRepository $repo) => $repo->createQueryBuilder('u')
                    ->where('u.id IN (:ids)')
                    ->setParameter('ids', $studentUserIds ?: [0])  //=> [0] pour éviter une erreur SQM si vide
                    ->orderBy('u.lastname', 'ASC'),
                'attr'          => [
                    'class' => 'form-select'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'preselected_room' => null,
            'coordinator'      => null,
        ]);
       
    }
    
}