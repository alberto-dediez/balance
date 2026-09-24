<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security\TwoFactor;

use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorToken;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TwoFactorTokenTest extends TestCase
{
    public function testHasNoRoles(): void
    {
        $sut = $this->createSUT(true);

        $this->assertSame([], $sut->getRoleNames());
        $this->assertNotEmpty($sut->authenticatedToken()->getRoleNames());
    }

    public function testDelegatesTheUser(): void
    {
        $sut = $this->createSUT(false);

        $this->assertSame('user@dediez.software', $sut->getUserIdentifier());
        $this->assertInstanceOf(SecurityUser::class, $sut->getUser());

        $other = new SecurityUser(UserMother::fromUsername('other@dediez.software'));
        $sut->setUser($other);

        $this->assertSame($other, $sut->authenticatedToken()->getUser());
    }

    public function testSerializationKeepsTheState(): void
    {
        $sut = $this->createSUT(true);
        $sut->setAttribute('key', 'value');

        $unserialized = unserialize(serialize($sut));

        $this->assertInstanceOf(TwoFactorToken::class, $unserialized);
        $this->assertTrue($unserialized->rememberMeRequested());
        $this->assertSame('main', $unserialized->firewallName());
        $this->assertSame('user@dediez.software', $unserialized->getUserIdentifier());
        $this->assertSame('value', $unserialized->getAttribute('key'));
        $this->assertSame([], $unserialized->getRoleNames());
    }

    private function createSUT(bool $rememberMe): TwoFactorToken
    {
        $user = new SecurityUser(UserMother::fromUsername('user@dediez.software'));

        return new TwoFactorToken(new UsernamePasswordToken($user, 'main', $user->getRoles()), 'main', $rememberMe);
    }
}
