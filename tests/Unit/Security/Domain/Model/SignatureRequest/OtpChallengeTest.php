<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\SignatureRequest;

use App\Security\Domain\Model\SignatureRequest\OtpChallenge;
use PHPUnit\Framework\TestCase;

class OtpChallengeTest extends TestCase
{
    private const APP_SECRET = 'test-otp-secret';

    public function testGenerateCreatesChallengWithSixDigitCode(): void
    {
        $challenge = OtpChallenge::generate(self::APP_SECRET);

        $this->assertNotEmpty($challenge->plainCode());
        $this->assertMatchesRegularExpression('/^\d{6}$/', $challenge->plainCode());
        $this->assertNotNull($challenge->codeHash());
        $this->assertNotNull($challenge->expiresAt());
        $this->assertEquals(0, $challenge->attempts());
        $this->assertNull($challenge->verifiedAt());
        $this->assertFalse($challenge->isVerified());
        $this->assertFalse($challenge->isExpired());
        $this->assertFalse($challenge->isLocked());
    }

    public function testVerifyReturnsTrueForCorrectCode(): void
    {
        $challenge = OtpChallenge::generate(self::APP_SECRET);

        $this->assertTrue($challenge->verify($challenge->plainCode(), self::APP_SECRET));
    }

    public function testVerifyReturnsFalseForWrongCode(): void
    {
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $wrongCode = '123456' === $challenge->plainCode() ? '654321' : '123456';

        $this->assertFalse($challenge->verify($wrongCode, self::APP_SECRET));
    }

    public function testVerifyReturnsFalseForWrongSecret(): void
    {
        $challenge = OtpChallenge::generate(self::APP_SECRET);

        $this->assertFalse($challenge->verify($challenge->plainCode(), 'wrong-secret'));
    }

    public function testVerifyReturnsFalseWhenExpired(): void
    {
        $expiredChallenge = new OtpChallenge(
            codeHash: 'somehash',
            expiresAt: new \DateTimeImmutable('-1 minute'),
            attempts: 0,
            verifiedAt: null,
            plainCode: '123456',
        );

        $this->assertTrue($expiredChallenge->isExpired());
        $this->assertFalse($expiredChallenge->verify('123456', self::APP_SECRET));
    }

    public function testVerifyReturnsFalseWhenLocked(): void
    {
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $locked = $challenge->withFailedAttempt()->withFailedAttempt()->withFailedAttempt();

        $this->assertTrue($locked->isLocked());
        $this->assertFalse($locked->verify($challenge->plainCode(), self::APP_SECRET));
    }

    public function testVerifyReturnsFalseWhenAlreadyVerified(): void
    {
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $verified = $challenge->withVerified(new \DateTimeImmutable());

        $this->assertFalse($verified->verify($challenge->plainCode(), self::APP_SECRET));
    }

    public function testWithFailedAttemptIncrementsAttempts(): void
    {
        $challenge = OtpChallenge::generate(self::APP_SECRET);

        $this->assertEquals(0, $challenge->attempts());
        $this->assertEquals(3, $challenge->attemptsLeft());

        $after1 = $challenge->withFailedAttempt();

        $this->assertEquals(1, $after1->attempts());
        $this->assertEquals(2, $after1->attemptsLeft());

        $after2 = $after1->withFailedAttempt();

        $this->assertEquals(2, $after2->attempts());
        $this->assertEquals(1, $after2->attemptsLeft());
    }

    public function testIsLockedAfterThreeFailedAttempts(): void
    {
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $locked = $challenge->withFailedAttempt()->withFailedAttempt()->withFailedAttempt();

        $this->assertTrue($locked->isLocked());
        $this->assertEquals(0, $locked->attemptsLeft());
    }

    public function testWithFailedAttemptIsImmutable(): void
    {
        $original = OtpChallenge::generate(self::APP_SECRET);
        $modified = $original->withFailedAttempt();

        $this->assertEquals(0, $original->attempts());
        $this->assertEquals(1, $modified->attempts());
    }

    public function testWithVerifiedSetsVerifiedAt(): void
    {
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $verifiedAt = new \DateTimeImmutable('2025-01-15 10:00:00');
        $verified = $challenge->withVerified($verifiedAt);

        $this->assertTrue($verified->isVerified());
        $this->assertEquals($verifiedAt, $verified->verifiedAt());
    }

    public function testWithVerifiedIsImmutable(): void
    {
        $original = OtpChallenge::generate(self::APP_SECRET);
        $verified = $original->withVerified(new \DateTimeImmutable());

        $this->assertFalse($original->isVerified());
        $this->assertTrue($verified->isVerified());
    }

    public function testPlainCodeIsEmptyWhenReconstructedWithoutGenerate(): void
    {
        $challenge = new OtpChallenge(
            codeHash: 'somehash',
            expiresAt: new \DateTimeImmutable('+15 minutes'),
            attempts: 0,
        );

        $this->assertEquals('', $challenge->plainCode());
    }

    public function testGeneratedChallengeExpiresInFuture(): void
    {
        $before = new \DateTimeImmutable();
        $challenge = OtpChallenge::generate(self::APP_SECRET);
        $after = new \DateTimeImmutable('+20 minutes');

        $this->assertGreaterThan($before, $challenge->expiresAt());
        $this->assertLessThan($after, $challenge->expiresAt());
    }
}
