<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Domain\Model\ApiToken;

use App\Security\Domain\Model\ApiToken\ApiTokenSecret;
use PHPUnit\Framework\TestCase;

class ApiTokenSecretTest extends TestCase
{
    public function testGeneratedValueHasPrefixAndBase64UrlBody(): void
    {
        $secret = ApiTokenSecret::generate();

        $this->assertMatchesRegularExpression('/^hyd_[A-Za-z0-9_-]{43}$/', $secret->plainValue());
    }

    public function testTwoGenerationsAreDifferent(): void
    {
        $this->assertNotEquals(ApiTokenSecret::generate()->plainValue(), ApiTokenSecret::generate()->plainValue());
    }

    public function testHashForIsDeterministicSha256Hex(): void
    {
        $hash = ApiTokenSecret::hashFor('hyd_value');

        $this->assertSame($hash, ApiTokenSecret::hashFor('hyd_value'));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    public function testHashMatchesHashForPlainValue(): void
    {
        $secret = ApiTokenSecret::generate();

        $this->assertSame(ApiTokenSecret::hashFor($secret->plainValue()), $secret->hash());
    }

    public function testPrefixHasTwelveCharacters(): void
    {
        $secret = ApiTokenSecret::generate();

        $this->assertSame(12, strlen($secret->prefix()));
        $this->assertStringStartsWith($secret->prefix(), $secret->plainValue());
        $this->assertStringStartsWith('hyd_', $secret->prefix());
    }
}
