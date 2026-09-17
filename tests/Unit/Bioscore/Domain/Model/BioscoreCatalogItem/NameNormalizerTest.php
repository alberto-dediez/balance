<?php

declare(strict_types=1);

namespace App\Tests\Unit\Bioscore\Domain\Model\BioscoreCatalogItem;

use App\Bioscore\Domain\Model\BioscoreCatalogItem\NameNormalizer;
use PHPUnit\Framework\TestCase;

class NameNormalizerTest extends TestCase
{
    public function testLowercasesAndTrims(): void
    {
        $this->assertSame('gas natural', NameNormalizer::normalize('  Gas Natural '));
    }

    public function testRemovesDiacritics(): void
    {
        $this->assertSame('gasoleo c', NameNormalizer::normalize('Gasóleo C'));
    }

    public function testCollapsesInnerSpaces(): void
    {
        $this->assertSame('gas natural licuado', NameNormalizer::normalize("Gas   natural \t licuado"));
    }

    public function testKeepsHyphensAndDigits(): void
    {
        $this->assertSame('r-410a', NameNormalizer::normalize('R-410A'));
    }
}
