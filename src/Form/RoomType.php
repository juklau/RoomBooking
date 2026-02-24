<?php

namespace App\Form;

use App\Entity\Room;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\Length;


class RoomType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'         => 'Nom de la salle',
                'constraints'   => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(max: 100, maxMessage: 'Le nom ne peut pas dépasser 100 caractères.')
                ],
                'attr' => [
                    'placeholder' => 'ex: Salle 102',
                    'class'       => 'form-control',
                ],
            ])

            ->add('capacity', IntegerType::class, [
                'label'         => 'Capacités (places)',
                'constraints'   => [
                    new NotBlank(message: 'La capacité est obligatoire.'),
                    new Positive(message: 'La capacité doit être un nombre positif.'),
                ],
                'attr' => [
                    'placeholder' => 'ex: 20',
                    'class'       => 'form-control',
                    'min'         => 1,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver):void
    {
        $resolver->setDefaults([
            'data_class' => Room::class
        ]);
    }




}