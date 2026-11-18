<?php

namespace App\Security\Voter;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may edit an order's details:
 *  - admin: every order;
 *  - staff: only orders they placed themselves (never other users' orders).
 */
final class OrderVoter extends Voter
{
    public const EDIT = 'ORDER_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::EDIT === $attribute && $subject instanceof Order;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isActive()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isStaff() && null !== $subject->getUser() && $subject->getUser()->getId() === $user->getId();
    }
}
