<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Ai\Anthropic;

use App\Shared\Infrastructure\Ai\Anthropic\AnthropicPricing;
use PHPUnit\Framework\TestCase;

class AnthropicPricingTest extends TestCase
{
    public function testComputesTheCostOfTheInputAndOutputTokens(): void
    {
        $this->assertSame(0.014, (new AnthropicPricing())->costOf('claude-sonnet-5-5', 3000, 800, 0, 0));
    }

    public function testComputesTheCostOfTheCacheTokens(): void
    {
        $this->assertSame(0.000825, (new AnthropicPricing())->costOf('claude-haiku-5-5', 1000, 1000, 10000, 1000));
    }

    public function testAnUnknownModelCostsNothing(): void
    {
        $this->assertSame(0.0, (new AnthropicPricing())->costOf('unknown-model', 3000, 800, 100, 100));
    }
}
