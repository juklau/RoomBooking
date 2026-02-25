<?php

namespace App\Form;

use App\Entity\Classe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CreateCoordinatorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label'       => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire.'),
                    new Length(max: 100),
                ],
                'attr' => [
                    'placeholder' => 'ex: Robert', 
                    'class'       => 'form-control'
                ],
            ])
            ->add('lastname', TextType::class, [
                'label'       => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(max: 100),
                ],
                'attr' => [
                    'placeholder' => 'ex: Dupont', 
                    'class'       => 'form-control'
                ],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Email',
                'constraints' => [
                    new NotBlank(message: "L'email est obligatoire."),
                    new Email(message: "L'email n'est pas valide."),
                ],
                'attr' => [
                    'placeholder' => 'robert@example.com', 
                    'class'       => 'form-control'
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type'           => PasswordType::class,
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr'  => [
                        'placeholder' => 'Mot de passe', 
                        'class'       => 'form-control'
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => [
                        'placeholder' => 'Confirmer le mot de passe', 
                        'class'       => 'form-control'
                    ],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints'     => [
                    new NotBlank(message: 'Le mot de passe est obligatoire.'),
                    new Length(
                        min: 8,
                        minMessage: 'Le mot de passe doit faire au moins 8 caractères.'
                    ),
                ],
            ])
            ->add('classes', EntityType::class, [
                'class'        => Classe::class,
                'choice_label' => 'name',
                'label'        => 'Classes assignées (optionnel)',
                'required'     => false,
                'multiple'     => true,     //coordinateur peut gérer plusieurs classes
                'expanded'     => false,   // affiche un <select multiple>
                'attr'         => [
                    'class' => 'form-select',
                    'size'  => 5,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}