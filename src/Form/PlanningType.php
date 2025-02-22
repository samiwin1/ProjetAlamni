<?php

namespace App\Form;

use App\Entity\Planning;
use App\Entity\User;
use App\Entity\Seance;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter course name',
                ],
            ])
            ->add('startTime', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'datetime-picker',
                ],
            ])
            ->add('endTime', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'datetime-picker',
                ],
            ])
            ->add('seance', EntityType::class, [
                'class' => Seance::class,
                'choice_label' => 'id', // Replace 'id' with a more meaningful field if available
                'required' => false,
                'placeholder' => 'Select a seance', // Add a placeholder
            ])
            ->add('teacher', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getNom() . ' ' . $user->getPrenom(); // Combine nom and prenom
                },
                'query_builder' => function (UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->setParameter('role', 'ROLE_ENSEIGNANT'); // Filter by the role field
                },
                'label' => 'Teacher',
                'required' => true,
                'placeholder' => 'Select a teacher', // Add a placeholder
            ])
            ->add('studentLevel', ChoiceType::class, [
                'label' => 'Student Level',
                'choices' => [
                    'Collège' => 'Collège',
                    'Lycée' => 'Lycée',
                    
                ],
                'required' => false,
                'placeholder' => 'Select student level', // Add a placeholder
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Planning::class,
            'attr' => [
                'novalidate' => 'novalidate', // Disable HTML5 validation
            ],
        ]);
    }
}