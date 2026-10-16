<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EconomicSector;

use App\CarbonFootprint\Application\EconomicSector\ClassificationCodeNormalizer;
use PHPUnit\Framework\TestCase;

class ClassificationCodeNormalizerTest extends TestCase
{
    public function testTrimsTheCode(): void
    {
        $this->assertSame('62.01', ClassificationCodeNormalizer::normalize(' 62.01 '));
    }

    public function testUppercasesTheCode(): void
    {
        $this->assertSame('ABC', ClassificationCodeNormalizer::normalize('abc'));
    }

    public function testEmptyCodeIsNull(): void
    {
        $this->assertNull(ClassificationCodeNormalizer::normalize(''));
        $this->assertNull(ClassificationCodeNormalizer::normalize('   '));
        $this->assertNull(ClassificationCodeNormalizer::normalize(null));
    }
}
