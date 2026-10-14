<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Infrastructure\Sso\Bioscore;

use App\Security\Infrastructure\Sso\Bioscore\BioscoreJwtDecoder;
use App\Security\Infrastructure\Sso\Bioscore\BioscoreSsoProvider;
use App\Security\Infrastructure\Sso\Bioscore\InMemory\InMemoryBioscoreClient;
use App\Security\Infrastructure\Sso\Bioscore\InMemory\InMemoryBioscoreJwtDecoder;
use App\Security\Infrastructure\Sso\SsoAuthenticationFailedException;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class BioscoreSsoProviderTest extends TestCase
{
    private InMemoryBioscoreClient $client;

    private InMemoryBioscoreJwtDecoder $jwtDecoder;

    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->client = new InMemoryBioscoreClient();
        $this->jwtDecoder = new InMemoryBioscoreJwtDecoder();
        $this->requestStack = new RequestStack();

        LocaleGetter::setLocale(null);
    }

    protected function tearDown(): void
    {
        LocaleGetter::setLocale(null);
    }

    public function testExposesItsKeyAndLabel(): void
    {
        $sut = $this->provider();

        $this->assertEquals('bioscore', $sut->key());
        $this->assertEquals('Bioscore', $sut->label());
    }

    public function testReflectsTheEnabledFlag(): void
    {
        $this->assertTrue($this->provider()->isEnabled());
        $this->assertFalse($this->provider(false)->isEnabled());
    }

    public function testBuildsTheLoginUrlWithTheRequestedLocale(): void
    {
        $this->requestStack->push(new Request(['locale' => 'en']));

        $this->assertEquals('https://sso.test/en/login-sso/bioscore', $this->provider()->loginUrl());
    }

    public function testBuildsTheLoginUrlWithTheCurrentLocaleWhenNoneIsRequested(): void
    {
        LocaleGetter::setLocale(Locale::French);
        $this->requestStack->push(new Request());

        $this->assertEquals('https://sso.test/fr/login-sso/bioscore', $this->provider()->loginUrl());
    }

    public function testReturnsTheIdentityOfAValidToken(): void
    {
        $this->jwtDecoder->setEmail('User@Dediez.Software');

        $identity = $this->provider()->authenticate(new Request(['token' => 'valid-token']));

        $this->assertEquals('user@dediez.software', $identity->email);
        $this->assertNull($identity->name);
    }

    public function testFailsWithoutToken(): void
    {
        $this->jwtDecoder->setEmail('user@dediez.software');

        $this->expectException(SsoAuthenticationFailedException::class);

        $this->provider()->authenticate(new Request());
    }

    public function testFailsWhenBioscoreRejectsTheToken(): void
    {
        $this->jwtDecoder->setEmail('user@dediez.software');
        $this->client->setValidToken(false);

        $this->expectException(SsoAuthenticationFailedException::class);

        $this->provider()->authenticate(new Request(['token' => 'rejected-token']));
    }

    public function testFailsWhenTheTokenHasNoEmail(): void
    {
        $this->expectException(SsoAuthenticationFailedException::class);

        $this->provider()->authenticate(new Request(['token' => 'valid-token']));
    }

    public function testFailsAndKeepsThePreviousExceptionWhenTheDecoderFails(): void
    {
        $failure = new \Exception('Invalid JWT signature.');
        $decoder = $this->createMock(BioscoreJwtDecoder::class);
        $decoder->method('decode')->willThrowException($failure);

        $sut = new BioscoreSsoProvider($this->client, $decoder, $this->requestStack, true);

        try {
            $sut->identityFromToken('broken-token');
        } catch (SsoAuthenticationFailedException $e) {
            $this->assertSame($failure, $e->getPrevious());

            return;
        }

        $this->fail('An SsoAuthenticationFailedException was expected');
    }

    private function provider(bool $enabled = true): BioscoreSsoProvider
    {
        return new BioscoreSsoProvider($this->client, $this->jwtDecoder, $this->requestStack, $enabled);
    }
}
