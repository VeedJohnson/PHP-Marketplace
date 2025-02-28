<?php

namespace App\Tests\Security;

use App\Entity\Advertisement;
use App\Entity\User;
use App\Security\UserActionVoter;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class UserActionVoterTest extends TestCase
{
    private UserActionVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new UserActionVoter();
    }

    /**
     * @throws Exception
     */
    private function createToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }

    /**
     * @throws Exception
     */
    public function testCreateAdAllowedForVerifiedUser(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $user->setVerified(true);
        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, null, [UserActionVoter::CREATE_AD])
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateAdDeniedForUnverifiedUser(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $user->setVerified(false);
        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, null, [UserActionVoter::CREATE_AD])
        );
    }

    /**
     * Test editing ads
     * @throws Exception
     */
    public function testEditAdAllowedForVerifiedAdOwner(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $user->setVerified(true);
        $ad = (new Advertisement())->setUser($user);

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $ad, [UserActionVoter::EDIT_AD])
        );
    }

    /**
     * @throws Exception
     */
    public function testEditAdDeniedForUnverifiedAdOwner(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $user->setVerified(false);
        $ad = (new Advertisement())->setUser($user);

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $ad, [UserActionVoter::EDIT_AD])
        );
    }

    /**
     * Test contacting sellers
     * @throws Exception
     */
    public function testContactSellerAllowedForVerifiedUser(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $user->setVerified(true);
        $adOwner = new User();
        $ad = (new Advertisement())->setUser($adOwner);

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $ad, [UserActionVoter::CONTACT_SELLER])
        );
    }

    /**
     * Test contacting sellers
     * @throws Exception
     */
    public function testContactSellerDeniedForUnverifiedUser(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $user->setVerified(false);
        $adOwner = new User();
        $ad = (new Advertisement())->setUser($adOwner);

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $ad, [UserActionVoter::CONTACT_SELLER])
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateAdAllowedForNonBannedUser(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->setVerified(true);
        $user->unban();
        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, null, [UserActionVoter::CREATE_AD])
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateAdDeniedForBannedUser(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->ban();
        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, null, [UserActionVoter::CREATE_AD])
        );
    }

    /**
     * @throws Exception
     */
    public function testEditAdAllowedForAdOwner(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $user->setVerified(true);
        $ad = (new Advertisement())->setUser($user);

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $ad, [UserActionVoter::EDIT_AD])
        );
    }

    /**
     * @throws Exception
     */
    public function testEditAdAllowedForModerator(): void
    {
        $user = (new User())->setRoles(['ROLE_MODERATOR']);
        $user->unban();
        $user->setVerified(true);
        $ad = new Advertisement();

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $ad, [UserActionVoter::EDIT_AD])
        );
    }

    /**
     * @throws Exception
     */
    public function testEditAdDeniedForNonOwnerNonModerator(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $adOwner = new User();
        $ad = (new Advertisement())->setUser($adOwner);

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $ad, [UserActionVoter::EDIT_AD])
        );
    }

    /**
     * @throws Exception
     */
    public function testContactSellerAllowedForDifferentUser(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $user->setVerified(true);
        $adOwner = new User();
        $ad = (new Advertisement())->setUser($adOwner);

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($token, $ad, [UserActionVoter::CONTACT_SELLER])
        );
    }

    /**
     * @throws Exception
     */
    public function testContactSellerDeniedForAdOwner(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->unban();
        $user->setVerified(true);
        $ad = (new Advertisement())->setUser($user);

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $ad, [UserActionVoter::CONTACT_SELLER])
        );
    }

    /**
     * @throws Exception
     */
    public function testContactSellerDeniedForBannedUser(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);
        $user->ban();
        $user->setVerified(true);
        $adOwner = new User();
        $ad = (new Advertisement())->setUser($adOwner);

        $token = $this->createToken($user);

        $this->assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($token, $ad, [UserActionVoter::CONTACT_SELLER])
        );
    }
}