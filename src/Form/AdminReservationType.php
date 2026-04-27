<?php

namespace App\Form;

use App\Entity\Room;
use App\Entity\User;
use App\Entity\Classe;
use App\Repository\UserRepository;
use App\Repository\ClasseRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;


class AdminReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        // Générer les créneaux de 08:00 à 20:00 par tranches de 30 min
            // ['08:00' => '08:00', '08:30' => '08:30', ..., '20:00' => '20:00']
        $timeSlots = [];
        for ($hour = 8; $hour <= 20; $hour++) {
            foreach ([0, 30] as $min) {

                if ($hour === 20 && $min === 30) break;             // => pas de 20:30

                $label = sprintf('%02d:%02d', $hour, $min);  // =>  formate les nombres sur 2 chiffres : '08:00'
                $timeSlots[$label] = $label;
            }
        }

        $builder
            ->add('room', EntityType::class, [ // EntityType::class => charge automatiquement toutes les Room depuis BDD
                'class'        => Room::class,
                'choice_label' => fn(Room $r) => $r->getName() . ' (' . $r->getCapacity() . 'places)',
                'label'        => 'Salle',
                'placeholder'  => '-- Choisir une salle --',
                'required'     => true,
                'constraints'  => [
                    new NotBlank(message: 'Veuillez choisir une salle.' 
                )],
                'data'         => $options['preselected_room'],
                // 'preferred_choices' => $options['preselected_room'] ? [$options['preselected_room']] : [],
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
                    // 'min'   => (new \DateTime('tomorrow'))->format('Y-m-d'), // interdit le passé
                    'min'   => (new \DateTime('today'))->format('Y-m-d'), // interdit le passé
                ],
            ])
            ->add('startTime', ChoiceType::class, [
                'label'       => 'Heure de début',
                'choices'     => $timeSlots,                            //select avec les créneaux horaires
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
                'required'      => false,                   //s'il est vide => l'admin réserve pour lui-même
                'query_builder' => fn(UserRepository $repo) => $repo->createQueryBuilder('u')
                    ->orderBy('u.lastname', 'ASC'),         //SELECT u.* FROM user u ORDER BY u.lastname ASC
                'attr'          => [
                    'class' => 'form-select'
                ],
            ])
            ->add('classe', EntityType::class, [
                'class'        => Classe::class,
                'choice_label' => 'name',
                'label'        => 'Classe concerné',
                'placeholder'  => '-- Choisir une classe --',
                'required'     => false,
                'attr'         => [
                    'class' => 'form-select',
                    'id'    => 'reservation_classe',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'preselected_room' => null,
        ]);
       
    }
    
}