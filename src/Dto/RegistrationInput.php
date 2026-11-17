<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RegistrationInput
{
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9_.]{3,30}$/',
        message: 'Username must be 3-30 characters (letters, numbers, underscore, or period).',
    )]
    #[Assert\NotBlank(message: 'Username must be 3-30 characters (letters, numbers, underscore, or period).')]
    public ?string $username = null;

    #[Assert\NotBlank(message: 'Enter a valid email address.')]
    #[Assert\Email(message: 'Enter a valid email address.')]
    public ?string $email = null;

    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Password must be at least 8 characters long.'),
        new Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long.'),
        new Assert\Regex(pattern: '/[A-Za-z]/', message: 'Password must contain at least one letter.'),
        new Assert\Regex(pattern: '/[0-9]/', message: 'Password must contain at least one number.'),
    ])]
    public ?string $password = null;

    #[Assert\Choice(choices: ['customer', 'staff'], message: 'Choose an account type.')]
    public ?string $account_type = 'customer';

    #[Assert\IdenticalTo(propertyPath: 'password', message: 'Passwords do not match.')]
    public ?string $confirm_password = null;
}
