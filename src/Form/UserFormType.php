<?php

namespace App\Form;

use App\Dto\UserData;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' => 'Name / username'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('role', ChoiceType::class, [
                'label' => 'Role',
                'choices' => ['Admin' => User::ROLE_ADMIN, 'Staff' => User::ROLE_STAFF, 'Customer' => User::ROLE_CUSTOMER],
                'disabled' => $options['lock_self'],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Account active (untick to disable login)',
                'required' => false,
                'disabled' => $options['lock_self'],
            ])
            ->add('password', PasswordType::class, [
                'label' => $options['is_new'] ? 'Password' : 'Reset password (leave blank to keep the current one)',
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserData::class,
            'is_new' => false,
            'lock_self' => false,
            'csrf_message' => 'Your session expired. Please try again.',
            'csrf_token_id' => 'user_form',
            'attr' => ['novalidate' => 'novalidate'],
        ]);
        $resolver->setAllowedTypes('is_new', 'bool');
        $resolver->setAllowedTypes('lock_self', 'bool');
    }
}
