<?php

namespace App\Form;

use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class RoomEquipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roomId = $options['room_id'];

        $builder

            // sélectionner un équipement existant
            ->add('existingEquipment', EntityType::class, [
                'class'         => Equipment::class,
                'choice_label'  => 'name',
                'label'         => 'Ajouter un équipement existant',
                'placeholder'   => '-- Choisir un équipement --',
                'required'      => false,

                // afficher seulement les équipements pas encore dans cette salle
                'query_builder' => fn(EquipmentRepository $repo) => $repo->createQueryBuilder('e')
                    ->where('NOT EXISTS (
                        SELECT 1 FROM App\Entity\Room r
                        JOIN r.equipments re
                        WHERE re.id = e.id 
                        AND r.id = :roomId
                    )')
                    ->setParameter('roomId', $roomId)
                    ->orderBy('e.name', 'ASC'),
                'attr' => [
                    'class' => 'form-select'
                    ],
            ])

            // créer un nouvel équipement
            ->add('newEquipment', TextType::class, [
                'label'       => 'Créer un nouvel équipement',
                'required'    => false,
                'constraints' => [
                    new Length(max: 150, maxMessage: 'Le nom ne peut pas dépasser 150 caractères.'),
                ],
                'attr' => [
                    'class'       => 'form-control',
                    'placeholder' => 'ex: Projecteur, Tableau blanc...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
        $resolver->setRequired('room_id');
    }
}