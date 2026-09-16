<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security;

use App\Security\Domain\Model\User\User;
use App\Security\Infrastructure\Persistence\InMemory\Repository\InMemoryUserRepository;
use App\Security\Infrastructure\Sso\InMemory\InMemorySsoProvider;
use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use App\Security\Infrastructure\Sso\SsoProviderRegistry;
use App\Security\Infrastructure\Symfony\Security\SecurityUser;
use App\Security\Infrastructure\Symfony\Security\SsoAuthenticator;
use App\Security\Infrastructure\Symfony\Security\UserProvider;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Security\Domain\Model\Account\AccountMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class SsoAuthenticatorTest extends TestCase
{
    private InMemorySsoProvider $google;

    private InMemorySsoProvider $microsoft;

    private InMemoryUserRepository $userRepository;

    protected function setUp(): void
    {
        $this->google = new InMemorySsoProvider('google', 'Google');
        $this->microsoft = new InMemorySsoProvider('microsoft', 'Microsoft');
        $this->userRepository = new InMemoryUserRepository();
    }

    public function testSupportsOnlyTheCallbackRoute(): void
    {
        $sut = $this->authenticator();

        $this->assertTrue($sut->supports($this->callbackRequest('google')));
        $this->assertFalse($sut->supports($this->request('app_login', [])));
    }

    public function testFailsWithAnUnknownProvider(): void
    {
        $sut = $this->authenticator();

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('SSO authentication could not be completed.');

        $sut->authenticate($this->callbackRequest('github'));
    }

    public function testFailsWithADisabledProvider(): void
    {
        $this->google->identifyAs('user@dediez.software');
        $this->google->disable();

        $sut = $this->authenticator();

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('SSO authentication could not be completed.');

        $sut->authenticate($this->callbackRequest('google'));
    }

    public function testFailsAndKeepsThePreviousExceptionWhenTheProviderFails(): void
    {
        $this->google->fail();

        $sut = $this->authenticator();

        try {
            $sut->authenticate($this->callbackRequest('google'));
        } catch (CustomUserMessageAuthenticationException $e) {
            $this->assertEquals('SSO authentication could not be completed.', $e->getMessageKey());
            $this->assertInstanceOf(SsoAuthenticationFailedException::class, $e->getPrevious());

            return;
        }

        $this->fail('A CustomUserMessageAuthenticationException was expected');
    }

    public function testBuildsAPassportWithTheEmailAndAnEnabledRememberMeBadge(): void
    {
        $this->userRepository->add(UserMother::fromUsername('user@dediez.software'));
        $this->google->identifyAs('user@dediez.software');

        $passport = $this->authenticator()->authenticate($this->callbackRequest('google'));

        $userBadge = $passport->getBadge(UserBadge::class);
        $rememberMeBadge = $passport->getBadge(RememberMeBadge::class);

        $this->assertInstanceOf(UserBadge::class, $userBadge);
        $this->assertEquals('user@dediez.software', $userBadge->getUserIdentifier());
        $this->assertInstanceOf(RememberMeBadge::class, $rememberMeBadge);
        $this->assertTrue($rememberMeBadge->isEnabled());
        $this->assertInstanceOf(SecurityUser::class, $passport->getUser());
    }

    public function testRejectsAnEmailWithoutUser(): void
    {
        $this->google->identifyAs('nobody@dediez.software');

        $passport = $this->authenticator()->authenticate($this->callbackRequest('google'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No user is registered with the identity provider email.');

        $passport->getUser();
    }

    public function testRejectsAUserOfAnInactiveAccount(): void
    {
        $account = AccountMother::fromName('Inactive');
        $account->changeStatus(false);

        $this->userRepository->add(UserMother::fromUsernameAndAccount('user@dediez.software', $account));
        $this->google->identifyAs('user@dediez.software');

        $passport = $this->authenticator()->authenticate($this->callbackRequest('google'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No user is registered with the identity provider email.');

        $passport->getUser();
    }

    public function testRedirectsToTheTargetPathOnSuccess(): void
    {
        $request = $this->callbackRequest('google');
        $request->getSession()->set('_security.main.target_path', '/es/profile');

        $response = $this->authenticator()->onAuthenticationSuccess($request, $this->tokenOf(UserMother::fromUsername('user@dediez.software')), 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/es/profile', $response->getTargetUrl());
    }

    public function testRedirectsToHomeInThePreferredLocaleOnSuccess(): void
    {
        $user = UserMother::fromUsername('user@dediez.software');
        $user->changePreferredLocale(Locale::English);

        $response = $this->authenticator()->onAuthenticationSuccess($this->callbackRequest('google'), $this->tokenOf($user), 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/en', $response->getTargetUrl());
    }

    public function testRedirectsToHomeInTheDefaultLocaleWhenThereIsNoPreference(): void
    {
        $response = $this->authenticator()->onAuthenticationSuccess($this->callbackRequest('google'), $this->tokenOf(UserMother::fromUsername('user@dediez.software')), 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/es', $response->getTargetUrl());
    }

    public function testStoresTheErrorInSessionAndRedirectsToLoginOnFailure(): void
    {
        $request = $this->callbackRequest('google');
        $exception = new CustomUserMessageAuthenticationException('SSO authentication could not be completed.');

        $response = $this->authenticator()->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/es/login', $response->getTargetUrl());
        $this->assertSame($exception, $request->getSession()->get(SecurityRequestAttributes::AUTHENTICATION_ERROR));
    }

    public function testRedirectsToTheLoginOfTheLocaleTheFlowStartedFrom(): void
    {
        $request = $this->callbackRequest('google');
        $request->getSession()->set(SsoAuthenticator::LOCALE_SESSION_KEY, 'en');

        $response = $this->authenticator()->onAuthenticationFailure($request, new CustomUserMessageAuthenticationException('SSO authentication could not be completed.'));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/en/login', $response->getTargetUrl());
    }

    private function authenticator(): SsoAuthenticator
    {
        return new SsoAuthenticator(
            new SsoProviderRegistry([$this->google, $this->microsoft]),
            new UserProvider($this->userRepository),
            $this->urlGenerator(),
            'es',
        );
    }

    private function urlGenerator(): UrlGeneratorInterface
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturnCallback(static function (string $route, array $parameters = []): string {
                $locale = $parameters['_locale'] ?? 'es';

                return 'home' === $route ? sprintf('/%s', $locale) : sprintf('/%s/login', $locale);
            });

        return $urlGenerator;
    }

    private function tokenOf(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(new SecurityUser($user));

        return $token;
    }

    private function callbackRequest(string $provider): Request
    {
        return $this->request('app_login_sso_callback', ['provider' => $provider]);
    }

    /** @param array<string, string> $attributes */
    private function request(string $route, array $attributes): Request
    {
        $request = new Request();
        $request->attributes->set('_route', $route);

        foreach ($attributes as $name => $value) {
            $request->attributes->set($name, $value);
        }

        $request->setSession(new Session(new MockArraySessionStorage()));

        return $request;
    }
}
