<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Event;

use App\Security\Application\User\TwoFactor\EffectiveTwoFactorMethod;
use App\Security\Domain\Model\User\TwoFactorMethod;
use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Symfony\Event\TwoFactorTokenCreatedListener;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTotpAuthenticator;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\InMemoryTwoFactorPolicy;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\SsoBadge;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorToken;
use App\Security\Infrastructure\Symfony\Security\TwoFactor\TwoFactorVerifiedBadge;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;

class TwoFactorTokenCreatedListenerTest extends TestCase
{
    public function testPasswordLoginWithTwoFactorReplacesTheToken(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $rememberMe = (new RememberMeBadge())->enable();
        $event = $this->passwordEvent($user, [$rememberMe]);

        $this->createSUT(new InMemoryTwoFactorPolicy())($event);

        $token = $event->getAuthenticatedToken();
        $this->assertInstanceOf(TwoFactorToken::class, $token);
        $this->assertTrue($token->rememberMeRequested());
        $this->assertFalse($rememberMe->isEnabled());
    }

    public function testPasswordLoginKeepsTheRememberMeRequestParameterAndTargetPath(): void
    {
        $user = UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email);
        $rememberMe = new RememberMeBadge();
        $event = $this->passwordEvent($user, [$rememberMe]);

        $request = Request::create('/es/login', 'POST', ['_remember_me' => 'on']);
        $session = new Session(new MockArraySessionStorage());
        $session->set('_security.main.target_path', '/es/profile');
        $request->setSession($session);

        $this->createSUT(new InMemoryTwoFactorPolicy(), $request)($event);

        $token = $event->getAuthenticatedToken();
        $this->assertInstanceOf(TwoFactorToken::class, $token);
        $this->assertTrue($token->rememberMeRequested());
        $this->assertFalse($rememberMe->isEnabled());
        $this->assertSame('/es/profile', $token->getAttribute(TwoFactorToken::TARGET_PATH_ATTRIBUTE));
    }

    public function testSsoLoginWithSsoFlagDisabledIsNotReplaced(): void
    {
        $event = $this->ssoEvent(UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp));

        $this->createSUT(new InMemoryTwoFactorPolicy())($event);

        $this->assertInstanceOf(UsernamePasswordToken::class, $event->getAuthenticatedToken());
    }

    public function testSsoLoginWithEmailMethodIsNotReplaced(): void
    {
        $event = $this->ssoEvent(UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email));

        $this->createSUT(new InMemoryTwoFactorPolicy(ssoEnabled: true))($event);

        $this->assertInstanceOf(UsernamePasswordToken::class, $event->getAuthenticatedToken());
    }

    public function testSsoLoginWithTotpMethodIsReplaced(): void
    {
        $event = $this->ssoEvent(UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Totp));

        $this->createSUT(new InMemoryTwoFactorPolicy(ssoEnabled: true))($event);

        $token = $event->getAuthenticatedToken();
        $this->assertInstanceOf(TwoFactorToken::class, $token);
        $this->assertTrue($token->rememberMeRequested());
    }

    public function testLoginWithoutPasswordNorSsoIsNotReplaced(): void
    {
        $user = new SecurityUser(UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email));
        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn(): SecurityUser => $user));
        $event = new AuthenticationTokenCreatedEvent(new UsernamePasswordToken($user, 'main', $user->getRoles()), $passport);

        $this->createSUT(new InMemoryTwoFactorPolicy(required: true))($event);

        $this->assertInstanceOf(UsernamePasswordToken::class, $event->getAuthenticatedToken());
    }

    public function testVerifiedLoginIsNotReplaced(): void
    {
        $event = $this->passwordEvent(UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email), [new TwoFactorVerifiedBadge()]);

        $this->createSUT(new InMemoryTwoFactorPolicy())($event);

        $this->assertInstanceOf(UsernamePasswordToken::class, $event->getAuthenticatedToken());
    }

    public function testRememberMeLoginIsNotReplaced(): void
    {
        $user = new SecurityUser(UserMother::withTwoFactorMethod('user@dediez.software', TwoFactorMethod::Email));
        $passport = new Passport(new UserBadge($user->getUserIdentifier(), fn(): SecurityUser => $user), new PasswordCredentials('apassword'));
        $event = new AuthenticationTokenCreatedEvent(new RememberMeToken($user, 'main'), $passport);

        $this->createSUT(new InMemoryTwoFactorPolicy(required: true))($event);

        $this->assertInstanceOf(RememberMeToken::class, $event->getAuthenticatedToken());
    }

    public function testUserWithoutTwoFactorInOptionalModeIsNotReplaced(): void
    {
        $event = $this->passwordEvent(UserMother::fromUsername('user@dediez.software'));

        $this->createSUT(new InMemoryTwoFactorPolicy())($event);

        $this->assertInstanceOf(UsernamePasswordToken::class, $event->getAuthenticatedToken());
    }

    /**
     * @param BadgeInterface[] $badges
     */
    private function passwordEvent(User $domainUser, array $badges = []): AuthenticationTokenCreatedEvent
    {
        $user = new SecurityUser($domainUser);
        $passport = new Passport(new UserBadge($user->getUserIdentifier(), fn(): SecurityUser => $user), new PasswordCredentials('apassword'), $badges);

        return new AuthenticationTokenCreatedEvent(new UsernamePasswordToken($user, 'main', $user->getRoles()), $passport);
    }

    private function ssoEvent(User $domainUser): AuthenticationTokenCreatedEvent
    {
        $user = new SecurityUser($domainUser);
        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn(): SecurityUser => $user), [(new RememberMeBadge())->enable(), new SsoBadge()]);

        return new AuthenticationTokenCreatedEvent(new UsernamePasswordToken($user, 'main', $user->getRoles()), $passport);
    }

    private function createSUT(InMemoryTwoFactorPolicy $policy, ?Request $request = null): TwoFactorTokenCreatedListener
    {
        $requestStack = new RequestStack();

        if ($request instanceof Request) {
            $requestStack->push($request);
        }

        return new TwoFactorTokenCreatedListener(new EffectiveTwoFactorMethod($policy, new InMemoryTotpAuthenticator()), $requestStack);
    }
}
