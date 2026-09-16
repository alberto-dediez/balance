<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Sso\OAuth2;

use App\Security\Infrastructure\Sso\OAuth2\GoogleSsoProvider;
use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GoogleUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class GoogleSsoProviderTest extends TestCase
{
    public function testRedirectsToTheAuthorizationUrl(): void
    {
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('redirect')
            ->with(['openid', 'email', 'profile'], [])
            ->willReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));

        $sut = new GoogleSsoProvider($this->clientRegistry($client), true);

        $this->assertEquals('https://accounts.google.com/o/oauth2/auth', $sut->loginUrl());
    }

    public function testReturnsTheIdentityOfAVerifiedEmail(): void
    {
        $sut = new GoogleSsoProvider($this->registryReturning([
            'sub' => '1',
            'email' => 'User@Dediez.Software',
            'email_verified' => true,
            'name' => 'A User',
        ]), true);

        $identity = $sut->authenticate(new Request());

        $this->assertEquals('user@dediez.software', $identity->email);
        $this->assertEquals('A User', $identity->name);
    }

    public function testFailsWhenTheEmailIsNotVerified(): void
    {
        $sut = new GoogleSsoProvider($this->registryReturning([
            'sub' => '1',
            'email' => 'user@dediez.software',
            'email_verified' => false,
            'name' => 'A User',
        ]), true);

        $this->expectException(SsoAuthenticationFailedException::class);

        $sut->authenticate(new Request());
    }

    public function testFailsWhenTheVerifiedClaimIsMissing(): void
    {
        $sut = new GoogleSsoProvider($this->registryReturning([
            'sub' => '1',
            'email' => 'user@dediez.software',
            'name' => 'A User',
        ]), true);

        $this->expectException(SsoAuthenticationFailedException::class);

        $sut->authenticate(new Request());
    }

    public function testFailsWhenTheEmailIsMissing(): void
    {
        $sut = new GoogleSsoProvider($this->registryReturning([
            'sub' => '1',
            'email_verified' => true,
            'name' => 'A User',
        ]), true);

        $this->expectException(SsoAuthenticationFailedException::class);

        $sut->authenticate(new Request());
    }

    public function testFailsAndKeepsThePreviousExceptionWhenTheProviderFails(): void
    {
        $failure = new IdentityProviderException('invalid_grant', 400, []);

        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->method('fetchUser')->willThrowException($failure);

        $sut = new GoogleSsoProvider($this->clientRegistry($client), true);

        try {
            $sut->authenticate(new Request());
        } catch (SsoAuthenticationFailedException $e) {
            $this->assertSame($failure, $e->getPrevious());

            return;
        }

        $this->fail('An SsoAuthenticationFailedException was expected');
    }

    public function testReflectsTheEnabledFlag(): void
    {
        $registry = $this->createMock(ClientRegistry::class);

        $this->assertFalse((new GoogleSsoProvider($registry, false))->isEnabled());
        $this->assertTrue((new GoogleSsoProvider($registry, true))->isEnabled());
    }

    /** @param array<string, mixed> $claims */
    private function registryReturning(array $claims): ClientRegistry
    {
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->method('fetchUser')->willReturn(new GoogleUser($claims));

        return $this->clientRegistry($client);
    }

    private function clientRegistry(OAuth2ClientInterface $client): ClientRegistry
    {
        $registry = $this->createMock(ClientRegistry::class);
        $registry->method('getClient')->with('google')->willReturn($client);

        return $registry;
    }
}
