<?php

namespace App\Security\Voter;

use App\Entity\StockMovement;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Who may edit a log entry:
 *  - staff: only entries they created themselves (not other staff, not admins);
 *  - admin: every entry, including those created by staff.
 */
final class StockMovementVoter extends Voter
{
    public const EDIT = 'LOG_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::EDIT === $attribute && $subject instanceof StockMovement;
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

        return $user->isStaff() && null !== $subject->getActor() && $subject->getActor()->getId() === $user->getId();
    }
}
