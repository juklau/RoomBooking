<?php

namespace App\Form;

use App\Entity\Classe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'       => 'Nom de la classe',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(
                        min: 2,
                        max: 100,
                        minMessage: 'Le nom doit faire au moins 2 caractères.',
                        maxMessage: 'Le nom ne peut pas dépasser 100 caractères.'
                    ),
                ],
                'attr' => [
                    'placeholder' => 'ex: BTS SIO SLAM B1',
                    'class'       => 'form-control',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Classe::class,
        ]);
    }
}