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
use Symfony\Component\Validator\Constraints\Regex;

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
                'attr'        => [
                    'class'       => 'form-control',
                    'placeholder' => 'ex: Robert', 
                ],
            ])
            ->add('lastname', TextType::class, [
                'label'       => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                    new Length(max: 100),
                ],
                'attr'        => [
                    'class'       => 'form-control',
                    'placeholder' => 'ex: Dupont', 
                ],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'Email',
                'constraints' => [
                    new NotBlank(message: 'L\'email est obligatoire.'),
                    new Email(message: 'Email invalide.'),
                ],
                'attr' => [
                    'class'        => 'form-control',
                    'placeholder'  => 'robert@example.com', 
                    'autocomplete' => 'email',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type'           => PasswordType::class,
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr'  => [
                        'class'        => 'form-control',
                        'placeholder'  => '••••••••',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => [
                        'class'       => 'form-control',
                        'placeholder' => '••••••••', 
                    ],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints'     => [
                    new NotBlank(message: 'Le mot de passe est obligatoire.'),
                    new Length(
                        min: 12,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
                        // dans ce cas limit = 12
                    ),
                    new Regex(
                        pattern: '/[a-z]/',
                        message: 'Le mot de passe doit contenir au moins une minuscule.'
                    ),
                    new Regex(
                        pattern: '/[A-Z]/',
                        message: 'Le mot de passe doit contenir au moins une majuscule.'
                    ),
                    new Regex(
                        pattern: '/[0-9]/',
                        message: 'Le mot de passe doit contenir au moins un chiffre.'
                    ),
                    new Regex(
                        pattern: '/[\W_]/',
                        message: 'Le mot de passe doit contenir au moins un caractère spécial.'
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