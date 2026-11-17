<?php

namespace App\Form;

use App\Dto\RegistrationInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class)
            ->add('account_type', ChoiceType::class, ['choices' => ['Customer' => 'customer', 'Staff (needs admin approval)' => 'staff']])
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('confirm_password', PasswordType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'data_class' => RegistrationInput::class,
            'csrf_message' => 'Your session expired. Please try again.',
            'csrf_token_id' => 'register_form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
