<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Length;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom de l\'événement ne peut pas être vide.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z\s]+$/',
                        'message' => 'Le nom de l\'événement doit contenir uniquement des lettres et des espaces.',
                    ]),
                    new Length([
                        'max' => 10,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'La description ne peut pas être vide.',
                    ]),
                ],
            ])
            ->add('date', null, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de l\'événement est requise.',
                    ]),
                ],
            ])
            ->add('heureDebut', null, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'heure de début est requise.',
                    ]),
                ],
            ])
            ->add('heureFin', null, [
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'heure de fin est requise.',
                    ]),
                ],
            ])
            ->add('lieu', null, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le lieu de l\'événement est requis.',
                    ]),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false, // Indique que ce champ ne correspond pas directement à un champ de l'entité
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'nom',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner une catégorie.',
                    ]),
                ],
            ]);

        // Ajout de la validation sur la date et les heures
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            // Vérifier si la date est supérieure à aujourd'hui
            if (isset($data['date']) && new \DateTime($data['date']) <= new \DateTime()) {
                $form->addError(new FormError("La date doit être supérieure à aujourd'hui."));
            }

        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
