<?php

namespace App\Dto;

use Symfony\Component\Security\Core\Validator\Constraints as SecurityAssert;
use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordData
{
    #[SecurityAssert\UserPassword(message: 'Your current password is not correct.')]
    #[Assert\NotBlank(message: 'Enter your current password.')]
    public ?string $currentPassword = null;

    #[Assert\Sequentially([
        new Assert\NotBlank(message: 'Password must be at least 8 characters long.'),
        new Assert\Length(min: 8, minMessage: 'Password must be at least 8 characters long.'),
        new Assert\Regex(pattern: '/[A-Za-z]/', message: 'Password must contain at least one letter.'),
        new Assert\Regex(pattern: '/[0-9]/', message: 'Password must contain at least one number.'),
    ])]
    public ?string $newPassword = null;

    #[Assert\IdenticalTo(propertyPath: 'newPassword', message: 'Passwords do not match.')]
    public ?string $confirmPassword = null;
}
