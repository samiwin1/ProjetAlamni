<?php

namespace App\Form;

use App\Entity\Reclamation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReclamationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user_email', EmailType::class, [
                'label' => 'Votre Email',
                'disabled' => true, // Empêche la modification
                'attr' => ['class' => 'form-control']
            ])
            ->add('role', TextType::class, [
                'label' => 'Votre rôle',
                'disabled' => true, // Empêche la modification
                'attr' => ['class' => 'form-control']
            ])
            ->add('admin_mail', EmailType::class, [
                'label' => 'Email de l\'administrateur',
                'attr' => ['class' => 'form-control']
            ])
            ->add('objet', TextType::class, [
                'label' => 'Objet de la Réclamation',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 5]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reclamation::class,
        ]);
    }
}
