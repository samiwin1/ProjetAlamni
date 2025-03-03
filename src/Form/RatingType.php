<?php

namespace App\Form;

use App\Entity\Cours;
use App\Entity\Rating;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value')
            ->add('comment')
            ->add('createdAt', null, [
                'widget' => 'single_text'
            ])
            ->add('user', EntityType::class, [
    'class' => User::class,
    'choice_label' => 'id',
    'multiple' => false, // ✅ Doit être `false` pour une relation ManyToOne
    'expanded' => false,
])

            ->add('cours', EntityType::class, [
                'class' => Cours::class,
'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rating::class,
        ]);
    }
}
