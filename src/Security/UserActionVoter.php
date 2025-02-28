<?php

namespace App\Security;

use App\Entity\User;
use App\Entity\Advertisement;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserActionVoter extends Voter
{
    const CREATE_AD = 'create_ad';
    const EDIT_AD = 'edit_ad';
    const CONTACT_SELLER = 'contact_seller';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::CREATE_AD,
            self::EDIT_AD,
            self::CONTACT_SELLER
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Check if user is banned
        if ($user->isBanned()) {
            return false;
        }

        return match($attribute) {
            self::CREATE_AD => $this->canCreateAd($user),
            self::EDIT_AD => $this->canEditAd($user, $subject),
            self::CONTACT_SELLER => $this->canContactSeller($user, $subject),
            default => false,
        };
    }

    private function canCreateAd(User $user): bool
    {
        if ($user->isBanned()) {
            return false;
        }

        return $user->isVerified();
    }


    private function canEditAd(User $user, Advertisement $ad): bool
    {
        if ($user->isBanned() || !$user->isVerified()) {
            return false;
        }

        return $user === $ad->getUser() ||
            in_array('ROLE_MODERATOR', $user->getRoles()) ||
            in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canContactSeller(User $user, Advertisement $ad): bool
    {
        if ($user === $ad->getUser() ||$user->isBanned()) {
            return false;
        }

        return $user->isVerified();
    }

}