<?php

namespace App\Form;

use App\Entity\Devoir;
use App\Entity\Cours;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\File;

class DevoirType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titreD', TextType::class, [
                'label' => 'Titre du devoir',
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le titre du devoir ne peut pas être vide.',
                    ]),
                    new Length([
                        'min' => 5,
                        'minMessage' => '',
                    ]),
                ],
            ])
            ->add('descrD', TextType::class, [
                'label' => 'Description',
                'required' => true,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank([
                        'message' => 'La description ne peut pas être vide.',
                    ]),
                ],
            ])
            ->add('dateD', DateTimeType::class, [
                'label' => 'Date de remise',
                'widget' => 'single_text',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de remise ne peut pas être vide.',
                    ]),
                ],
            ])
            ->add('comment', TextType::class, [
                'label' => 'Commentaire',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('supportD', FileType::class, [
                'label' => 'Support de devoir (PDF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un document PDF valide.',
                    ])
                ],
            ]);

        if (!$builder->getData() || !$builder->getData()->getCours()) {
            $builder->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'titre',
                'label' => 'Cours associé',
                'placeholder' => 'Choisissez un cours',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un cours.',
                    ]),
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Devoir::class,
        ]);
    }
}