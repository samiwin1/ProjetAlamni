<?php

namespace App\Form;

use App\Entity\Cours;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\File;

class CoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre du cours',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => '',
                    ]),
                    new Length([
                        'min' => 5,
                        'minMessage' => '',
                    ]),
                ],
            ])
            ->add('descrC', TextType::class, [
                'label' => 'Description',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => '',
                    ]),
                ],
            ])
            ->add('matiereC', TextType::class, [
                'label' => 'Matière',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => '',
                    ]),
                ],
            ])
            ->add('dateC', DateTimeType::class, [
                'label' => 'Date du cours',
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date du cours ne peut pas être vide.',
                    ]),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image (JPG, PNG file)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG ou PNG)',
                    ])
                ],
            ])
            ->add('supportC', FileType::class, [
                'label' => 'Support de cours (PDF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un document PDF valide',
                    ])
                ],
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
                ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
        ]);
    }
}