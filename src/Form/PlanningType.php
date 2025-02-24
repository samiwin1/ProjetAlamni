<?php

namespace App\Form;

use App\Entity\Planning;
use App\Entity\Seance;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanningType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false, // Disable Symfony's required validation
            ])
            ->add('startTime', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false, // Disable Symfony's required validation
            ])
            ->add('endTime', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => false, // Disable Symfony's required validation
            ])
            ->add('seance', EntityType::class, [
                'class' => Seance::class,
                'choice_label' => 'id',
                'required' => false, // Disable Symfony's required validation
            ])
        ;
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