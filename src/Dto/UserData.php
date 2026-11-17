<?php

namespace App\Dto;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class UserData
{
    #[Assert\NotBlank(message: 'Username must be 3-30 characters (letters, numbers, underscore, or period).')]
    #[Assert\Regex(
        pattern: '/^[A-Za-z0-9_.]{3,30}$/',
        message: 'Username must be 3-30 characters (letters, numbers, underscore, or period).',
    )]
    public ?string $username = null;

    #[Assert\NotBlank(message: 'Enter a valid email address.')]
    #[Assert\Email(message: 'Enter a valid email address.')]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\Choice(choices: User::ROLES, message: 'Choose a valid role.')]
    public ?string $role = User::ROLE_STAFF;

    public bool $active = true;

    #[Assert\When(
        expression: 'this.passwordRequired or this.password',
        constraints: [new Assert\Sequentially([
            new Assert\NotBlank(message: 'Password must be at least 8 characters long.'),
            new Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long.'),
            new Assert\Regex(pattern: '/[A-Za-z]/', message: 'Password must contain at least one letter.'),
            new Assert\Regex(pattern: '/[0-9]/', message: 'Password must contain at least one number.'),
        ])],
    )]
    public ?string $password = null;

    public bool $passwordRequired = false;

    public static function fromUser(User $user): self
    {
        $data = new self();
        $data->username = $user->getUsername();
        $data->email = $user->getEmail();
        $data->role = $user->getRole();
        $data->active = $user->isActive();

        return $data;
    }
}
