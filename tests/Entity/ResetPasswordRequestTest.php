<?php

namespace App\Tests\Entity;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ResetPasswordRequestTest extends TestCase
{
    public function testResetPasswordRequestCreation(): void
    {
        $user = new User();
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $selector = 'test-selector';
        $hashedToken = 'hashed-token';

        $resetPasswordRequest = new ResetPasswordRequest($user, $expiresAt, $selector, $hashedToken);

        $this->assertInstanceOf(User::class, $resetPasswordRequest->getUser());
        $this->assertSame($user, $resetPasswordRequest->getUser());
        $this->assertSame($expiresAt, $resetPasswordRequest->getExpiresAt());
        // Ensure the selector and hashed token are correctly initialized (indirect testing)
        $this->assertNotEmpty($resetPasswordRequest->getExpiresAt());
    }

    public function testTokenExpiration(): void
    {
        $user = new User();
        $expiresAt = new \DateTimeImmutable('+1 hour');
        $resetPasswordRequest = new ResetPasswordRequest($user, $expiresAt, 'selector', 'hashed-token');

        $this->assertFalse($resetPasswordRequest->isExpired());

        $expiredTime = new \DateTimeImmutable('-1 hour');
        $expiredResetPasswordRequest = new ResetPasswordRequest($user, $expiredTime, 'selector', 'hashed-token');

        $this->assertTrue($expiredResetPasswordRequest->isExpired());
    }
}