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
                'required' => false, // Allow null values
                'attr' => [
                    'placeholder' => 'Enter course name',
                ],
            ])
            ->add('startTime', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false, // Allow null values
                'attr' => [
                    'class' => 'datetime-picker',
                ],
                'empty_data' => null, // Explicitly set empty data to null
            ])
            ->add('endTime', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false, // Allow null values
                'attr' => [
                    'class' => 'datetime-picker',
                ],
                'empty_data' => null, // Explicitly set empty data to null
            ])
            ->add('seance', EntityType::class, [
                'class' => Seance::class,
                'choice_label' => 'id',
                'required' => false, // Allow null values
                'placeholder' => 'Select a seance',
            ])
            ->add('teacher', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getNom() . ' ' . $user->getPrenom();
                },
                'query_builder' => function (UserRepository $userRepository) {
                    return $userRepository->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->setParameter('role', 'ROLE_ENSEIGNANT');
                },
                'label' => 'Teacher',
                'required' => false, // Allow null values
                'placeholder' => 'Select a teacher',
            ])
            ->add('studentLevel', ChoiceType::class, [
                'label' => 'Student Level',
                'choices' => [
                    'Collège' => 'Collège',
                    'Lycée' => 'Lycée',
                ],
                'required' => false, // Allow null values
                'placeholder' => 'Select student level',
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