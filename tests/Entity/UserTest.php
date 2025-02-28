<?php

namespace App\Tests\Entity;

use App\Entity\Advertisement;
use App\Entity\User;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testSetAndGetEmail(): void
    {
        $user = new User();
        $email = 'test@example.com';

        $user->setEmail($email);

        $this->assertSame($email, $user->getEmail());
        $this->assertSame($email, $user->getUserIdentifier());
    }

    public function testSetAndGetRoles(): void
    {
        $user = new User();
        $roles = ['ROLE_ADMIN'];

        $user->setRoles($roles);

        $this->assertSame(array_unique(array_merge($roles, ['ROLE_USER'])), $user->getRoles());
    }

    public function testSetAndGetPassword(): void
    {
        $user = new User();
        $password = 'hashed_password';

        $user->setPassword($password);

        $this->assertSame($password, $user->getPassword());
    }

    public function testBanAndUnban(): void
    {
        $user = new User();
        $reason = 'Violation of rules';
        $banUntil = new \DateTimeImmutable('+1 day');

        $user->ban($reason, $banUntil);

        $this->assertTrue($user->isBanned());
        $this->assertSame($reason, $user->getBanReason());
        $this->assertNotNull($user->getBannedAt());
        $this->assertEquals($banUntil, $user->getBannedUntil());

        $user->unban();

        $this->assertFalse($user->isBanned());
        $this->assertNull($user->getBanReason());
        $this->assertNull($user->getBannedAt());
        $this->assertNull($user->getBannedUntil());
    }

    public function testRemainingBanDuration(): void
    {
        $user = new User();
        $banUntil = new \DateTimeImmutable('+59 minutes');

        $user->ban('Reason', $banUntil);

        $remainingDuration = $user->getRemainingBanDuration();

        // Assert the format based on the remaining time
        $this->assertTrue(
            str_contains($remainingDuration, 'days') ||
            str_contains($remainingDuration, 'hours') ||
            str_contains($remainingDuration, 'minutes'),
            "Expected remaining ban duration to be in days, hours, or minutes, but got: $remainingDuration"
        );

        $user->unban();
        $this->assertNull($user->getRemainingBanDuration());
    }

    /**
     * @throws Exception
     */
    public function testAddAndRemoveAdvertisement(): void
    {
        $user = new User();
        $ad = new Advertisement();

        // Add advertisement
        $user->addAdvertisement($ad);
        $this->assertCount(1, $user->getAdvertisements());

        // Remove advertisement
        $user->removeAdvertisement($ad);
        $this->assertCount(0, $user->getAdvertisements());
    }
    public function testIsVerified(): void
    {
        $user = new User();

        $this->assertFalse($user->isVerified());

        $user->setVerified(true);
        $this->assertTrue($user->isVerified());
    }

    public function testIsBannedAutoUnban(): void
    {
        $user = new User();
        $user->ban('Reason', new \DateTimeImmutable('-1 day'));

        $this->assertFalse($user->isBanned());
    }

    public function testGetCreatedAt(): void
    {
        $user = new User();
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
    }
}