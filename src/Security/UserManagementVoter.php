<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserManagementVoter extends Voter
{
    const BAN_USER = 'ban_user';
    const DELETE_USER = 'delete_user';
    const PROMOTE_USER = 'promote_user';
    const MODIFY_USER = 'modify_user';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                self::BAN_USER,
                self::DELETE_USER,
                self::PROMOTE_USER,
                self::MODIFY_USER
            ]) && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;

        // Super admin cannot be modified by anyone
        if (in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles())) {
            return $currentUser === $targetUser;
        }

        // Check if current user is a super admin
        $isSuperAdmin = in_array('ROLE_SUPER_ADMIN', $currentUser->getRoles());

        // Check if current user is an admin
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());

        return match($attribute) {
            self::PROMOTE_USER => $this->canPromoteUser($currentUser, $targetUser, $isSuperAdmin),
            self::BAN_USER => $this->canBanUser($currentUser, $targetUser, $isSuperAdmin, $isAdmin),
            self::DELETE_USER => $this->canDeleteUser($currentUser, $targetUser, $isSuperAdmin, $isAdmin),
            self::MODIFY_USER => $this->canModifyUser($currentUser, $targetUser, $isSuperAdmin, $isAdmin),
            default => false,
        };
    }

    private function canPromoteUser(User $currentUser, User $targetUser, bool $isSuperAdmin): bool
    {
        // Only super admin can promote users to admin
        if (!$isSuperAdmin) {
            return false;
        }

        // Cannot promote to super admin
        $newRoles = $targetUser->getRoles();
        if (in_array('ROLE_SUPER_ADMIN', $newRoles)) {
            return false;
        }

        return true;
    }

    private function canBanUser(User $currentUser, User $targetUser, bool $isSuperAdmin, bool $isAdmin): bool
    {
        // Super admin can ban anyone except other super admins
        if ($isSuperAdmin) {
            return !in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles());
        }

        if ($isAdmin) {
            return !in_array('ROLE_ADMIN', $targetUser->getRoles()) &&
                !in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles());
        }

        return false;
    }

    private function canDeleteUser(User $currentUser, User $targetUser, bool $isSuperAdmin, bool $isAdmin): bool
    {
        return $this->canBanUser($currentUser, $targetUser, $isSuperAdmin, $isAdmin);
    }

    private function canModifyUser(User $currentUser, User $targetUser, bool $isSuperAdmin, bool $isAdmin): bool
    {
        // Users can modify their own non-critical settings
        if ($currentUser === $targetUser) {
            return true;
        }

        // Super admin can modify anyone except other super admins
        if ($isSuperAdmin) {
            return !in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles());
        }

        // Admin can modify regular users and moderators
        if ($isAdmin) {
            return !in_array('ROLE_ADMIN', $targetUser->getRoles()) &&
                !in_array('ROLE_SUPER_ADMIN', $targetUser->getRoles());
        }

        return false;
    }
}