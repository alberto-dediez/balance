<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Symfony\Security;

use App\Security\Infrastructure\SSO\InMemory\InMemorySSOClient;
use App\Security\Infrastructure\SSO\SSOJwtDecoder;
use App\Security\Infrastructure\Symfony\Security\SSOAuthenticator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class SSOAuthenticatorTest extends TestCase
{
    private InMemorySSOClient $ssoClient;
    /** @var UserProviderInterface<UserInterface>&MockObject */
    private UserProviderInterface&MockObject $userProvider;
    private SSOJwtDecoder&MockObject $jwtDecoder;
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private SSOAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->ssoClient = new InMemorySSOClient('https://sso.test', true);
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->jwtDecoder = $this->createMock(SSOJwtDecoder::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->authenticator = new SSOAuthenticator(
            $this->ssoClient,
            $this->userProvider,
            $this->jwtDecoder,
            $this->urlGenerator,
        );
    }

    public function testSupportsReturnsTrue(): void
    {
        $request = new Request(['token' => 'valid-token']);
        $request->attributes->set('_route', 'app_login_sso_callback');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseWhenRouteDoesNotMatch(): void
    {
        $request = new Request(['token' => 'valid-token']);
        $request->attributes->set('_route', 'other_route');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseWhenTokenIsMissing(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'app_login_sso_callback');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateThrowsExceptionWhenTokenIsInvalid(): void
    {
        $this->ssoClient->setValidToken(false);

        $request = new Request(['token' => 'invalid-token']);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Invalid SSO token.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWhenDecoderFails(): void
    {
        $request = new Request(['token' => 'valid-token']);

        $this->jwtDecoder->method('decode')
            ->with('valid-token')
            ->willThrowException(new \Exception('Decoding failed.'));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Failed to decode or validate SSO token: Decoding failed.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWhenEmailIsMissing(): void
    {
        $request = new Request(['token' => 'valid-token']);

        $this->jwtDecoder->method('decode')
            ->with('valid-token')
            ->willReturn(['sub' => '123']); // No email in payload

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Failed to decode or validate SSO token: Email not found in SSO token.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateSuccessReturnsPassport(): void
    {
        $request = new Request(['token' => 'valid-token']);

        $this->jwtDecoder->method('decode')
            ->with('valid-token')
            ->willReturn(['email' => 'user@example.com']);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(SelfValidatingPassport::class, $passport);

        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        $this->assertEquals('user@example.com', $badge->getUserIdentifier());

        // Test the user loader inside the UserBadge
        $userMock = $this->createMock(UserInterface::class);
        $this->userProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('user@example.com')
            ->willReturn($userMock);

        $loader = $badge->getUserLoader();

        self::assertIsCallable($loader);

        $loadedUser = $loader('user@example.com');

        $this->assertSame($userMock, $loadedUser);
    }

    public function testUserBadgeThrowsAuthenticationExceptionIfUserNotFound(): void
    {
        $request = new Request(['token' => 'valid-token']);
        $this->jwtDecoder->method('decode')->willReturn(['email' => 'notfound@example.com']);

        $passport = $this->authenticator->authenticate($request);

        /** @var UserBadge $badge */
        $badge = $passport->getBadge(UserBadge::class);
        $loader = $badge->getUserLoader();

        self::assertIsCallable($loader);

        $this->userProvider->expects($this->once())
            ->method('loadUserByIdentifier')
            ->with('notfound@example.com')
            ->willThrowException(new UserNotFoundException());

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('User not found in our system.');

        $loader('notfound@example.com');
    }

    public function testOnAuthenticationSuccessWithoutTargetPath(): void
    {
        $request = new Request();
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturn(null);
        $request->setSession($session);

        $token = $this->createMock(TokenInterface::class);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('home')
            ->willReturn('/home');

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/home', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessWithTargetPath(): void
    {
        $request = new Request();
        $session = $this->createMock(Session::class);

        // TargetPathTrait uses the session key '_security.main.target_path'
        $session->expects($this->once())
            ->method('get')
            ->with('_security.main.target_path')
            ->willReturn('/custom-target');

        $request->setSession($session);

        $token = $this->createMock(TokenInterface::class);

        $response = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/custom-target', $response->getTargetUrl());
    }

    public function testOnAuthenticationFailure(): void
    {
        $request = new Request();
        $session = $this->createMock(Session::class);
        $flashBag = $this->createMock(FlashBagInterface::class);

        $session->method('getFlashBag')->willReturn($flashBag);
        $request->setSession($session);

        $exception = new AuthenticationException('Custom error message');

        $flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'Custom error message');

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('app_login')
            ->willReturn('/login');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getTargetUrl());
    }

    public function testStart(): void
    {
        $request = new Request();

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('app_login')
            ->willReturn('/login');

        $response = $this->authenticator->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getTargetUrl());
    }
}
