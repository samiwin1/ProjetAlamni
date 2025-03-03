<?php
namespace App\Form;

use App\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le message ne peut pas être vide.'
                    ]),
                    new Assert\Length([
                        'min' => 2,
                        'minMessage' => 'Le message doit contenir au moins 2 caractères.',
                        'max' => 50,
                        'maxMessage' => 'Le message ne peut pas dépasser 50 caractères.'
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 50,
                    'placeholder' => 'Écrivez votre message...',
                    'oninput' => 'validateMessage(this)'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
        ]);
    }
}

?>

<script>
function validateMessage(input) {
    const errorElement = document.getElementById('message-error');
    if (!errorElement) {
        return;
    }

    const value = input.value.trim();
    if (value.length < 2) {
        errorElement.textContent = 'Le message doit contenir au moins 2 caractères.';
        errorElement.style.display = 'block';
    } else if (value.length > 50) {
        errorElement.textContent = 'Le message ne peut pas dépasser 50 caractères.';
        errorElement.style.display = 'block';
    } else {
        errorElement.style.display = 'none';
    }
}
</script>

<div id="message-error" class="text-danger" style="display: none;"></div>
