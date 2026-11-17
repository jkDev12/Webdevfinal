<?php

namespace App\Form;

use App\Dto\ChangePasswordData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, ['label' => 'Current password', 'attr' => ['autocomplete' => 'current-password']])
            ->add('newPassword', PasswordType::class, ['label' => 'New password', 'attr' => ['autocomplete' => 'new-password']])
            ->add('confirmPassword', PasswordType::class, ['label' => 'Confirm new password', 'attr' => ['autocomplete' => 'new-password']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangePasswordData::class,
            'csrf_message' => 'Your session expired. Please try again.',
            'csrf_token_id' => 'change_password',
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
