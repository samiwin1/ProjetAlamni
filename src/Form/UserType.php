<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom est requis'
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s-]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces et tirets'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le prénom est requis'
                    ]),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s-]+$/',
                        'message' => 'Le prénom ne peut contenir que des lettres, espaces et tirets'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre prénom'
                ]
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'email est requis'
                    ]),
                    new Email([
                        'message' => 'L\'email "{{ value }}" n\'est pas valide'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Votre email'
                ]
            ]);

        if ($options['is_edit']) {
            $builder->add('mot_de_passe', PasswordType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Laissez vide pour garder le même mot de passe'
                ]
            ]);
        } else {
            $builder
                ->add('mot_de_passe', PasswordType::class, [
                    'required' => true,
                    'mapped' => true,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Minimum 8 caractères'
                    ]
                ])
                ->add('role', ChoiceType::class, [
                    'required' => true,
                    'choices' => [
                        'Élève' => 'ROLE_ELEVE',
                        'Enseignant' => 'ROLE_ENSEIGNANT',
                        'Parent' => 'ROLE_PARENT',
                        'Administrateur' => 'ROLE_ADMIN'
                    ],
                    'attr' => [
                        'class' => 'form-control'
                    ]
                ])
                ->add('niveau', ChoiceType::class, [
                    'required' => false,
                    'choices' => [
                        'Collège' => 'college',
                        'Lycée' => 'lycee'
                    ],
                    'placeholder' => 'Sélectionnez un niveau',
                    'attr' => [
                        'class' => 'form-control'
                    ]
                ])
                ->add('nom_niveau', TextType::class, [
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Ex: 6ème, 5ème, 2nde...'
                    ]
                ]);
        }

        $builder->add('photo', FileType::class, [
            'required' => false,
            'mapped' => false,
            'constraints' => [
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'image/jpeg',
                        'image/png',
                        'image/gif'
                    ],
                    'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF)',
                    'maxSizeMessage' => 'L\'image ne doit pas dépasser 5 Mo'
                ])
            ],
            'attr' => [
                'class' => 'form-control',
                'accept' => 'image/*'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false,
            'csrf_protection' => true,
            'validation_groups' => ['Default']
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}