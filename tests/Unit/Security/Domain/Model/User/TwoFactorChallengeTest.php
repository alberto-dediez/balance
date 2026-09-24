<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\User;

use App\Security\Domain\Model\User\TwoFactorChallenge;
use PHPUnit\Framework\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    private const APP_SECRET = 'test-app-secret';

    public function testGenerateProducesSixDigitCode(): void
    {
        $challenge = TwoFactorChallenge::generate(self::APP_SECRET);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $challenge->plainCode());
        $this->assertNotNull($challenge->codeHash());
        $this->assertNotEquals($challenge->plainCode(), $challenge->codeHash());
        $this->assertTrue($challenge->isPending());
    }

    public function testVerifyAcceptsTheGeneratedCode(): void
    {
        $challenge = TwoFactorChallenge::generate(self::APP_SECRET);

        $this->assertTrue($challenge->verify($challenge->plainCode(), self::APP_SECRET));
    }

    public function testVerifyRejectsAWrongCode(): void
    {
        $challenge = TwoFactorChallenge::generate(self::APP_SECRET);

        $wrongCode = '000000' === $challenge->plainCode() ? '111111' : '000000';

        $this->assertFalse($challenge->verify($wrongCode, self::APP_SECRET));
    }

    public function testVerifyRejectsAnExpiredChallenge(): void
    {
        $challenge = TwoFactorChallenge::generate(self::APP_SECRET);

        $expired = new TwoFactorChallenge(
            codeHash: $challenge->codeHash(),
            expiresAt: new \DateTimeImmutable('-1 minute'),
            attempts: 0,
        );

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($expired->isPending());
        $this->assertFalse($expired->verify($challenge->plainCode(), self::APP_SECRET));
    }

    public function testVerifyRejectsALockedChallenge(): void
    {
        $challenge = TwoFactorChallenge::generate(self::APP_SECRET);

        $locked = $challenge->withFailedAttempt()->withFailedAttempt()->withFailedAttempt();

        $this->assertTrue($locked->isLocked());
        $this->assertFalse($locked->verify($challenge->plainCode(), self::APP_SECRET));
    }

    public function testVerifyRejectsAChallengeWithoutCode(): void
    {
        $challenge = TwoFactorChallenge::withoutCode();

        $this->assertNull($challenge->codeHash());
        $this->assertFalse($challenge->verify('123456', self::APP_SECRET));
    }

    public function testWithFailedAttemptIncrementsTheAttempts(): void
    {
        $challenge = TwoFactorChallenge::generate(self::APP_SECRET);

        $this->assertEquals(3, $challenge->attemptsLeft());

        $failed = $challenge->withFailedAttempt();

        $this->assertEquals(2, $failed->attemptsLeft());
        $this->assertFalse($failed->isLocked());
        $this->assertSame('', $failed->plainCode());
    }

    public function testIsLockedAfterTheThirdFailure(): void
    {
        $challenge = TwoFactorChallenge::generate(self::APP_SECRET)
            ->withFailedAttempt()
            ->withFailedAttempt();

        $this->assertFalse($challenge->isLocked());

        $challenge = $challenge->withFailedAttempt();

        $this->assertTrue($challenge->isLocked());
        $this->assertEquals(0, $challenge->attemptsLeft());
        $this->assertFalse($challenge->isPending());
    }

    public function testChallengeWithoutCodeIsPendingAndNoneIsNot(): void
    {
        $this->assertTrue(TwoFactorChallenge::withoutCode()->isPending());
        $this->assertFalse(TwoFactorChallenge::none()->isPending());
    }
}
