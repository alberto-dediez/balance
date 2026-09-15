<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Sso\OAuth2;

use App\Security\Infrastructure\Sso\OAuth2\MicrosoftSsoProvider;
use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;
use TheNetworg\OAuth2\Client\Token\AccessToken;

class MicrosoftSsoProviderTest extends TestCase
{
    public function testRedirectsToTheAuthorizationUrl(): void
    {
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client
            ->expects($this->once())
            ->method('redirect')
            ->with(['openid', 'profile', 'email'], [])
            ->willReturn(new RedirectResponse('https://login.microsoftonline.com/common/oauth2/v2.0/authorize'));

        $sut = new MicrosoftSsoProvider($this->clientRegistry($client), true);

        $this->assertEquals('https://login.microsoftonline.com/common/oauth2/v2.0/authorize', $sut->loginUrl());
    }

    public function testReturnsTheIdentityOfTheEmailClaim(): void
    {
        $sut = new MicrosoftSsoProvider($this->registryReturning([
            'oid' => '1',
            'email' => 'User@Dediez.Software',
            'name' => 'A User',
        ]), true);

        $identity = $sut->authenticate(new Request());

        $this->assertEquals('user@dediez.software', $identity->email);
        $this->assertEquals('A User', $identity->name);
    }

    public function testFailsWhenTheEmailClaimIsMissing(): void
    {
        $sut = new MicrosoftSsoProvider($this->registryReturning([
            'oid' => '1',
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

        $sut = new MicrosoftSsoProvider($this->clientRegistry($client), true);

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

        $this->assertFalse((new MicrosoftSsoProvider($registry, false))->isEnabled());
        $this->assertTrue((new MicrosoftSsoProvider($registry, true))->isEnabled());
    }

    /** @param array<string, mixed> $claims */
    private function registryReturning(array $claims): ClientRegistry
    {
        $client = $this->createMock(OAuth2ClientInterface::class);
        $client->method('fetchUser')->willReturn(new AzureResourceOwner($claims, $this->createMock(AccessToken::class)));

        return $this->clientRegistry($client);
    }

    private function clientRegistry(OAuth2ClientInterface $client): ClientRegistry
    {
        $registry = $this->createMock(ClientRegistry::class);
        $registry->method('getClient')->with('microsoft')->willReturn($client);

        return $registry;
    }
}
