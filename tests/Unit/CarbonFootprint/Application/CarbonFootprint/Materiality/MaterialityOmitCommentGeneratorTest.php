<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprint\Materiality;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialityOmitCommentGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprint\MaterialityDecisionInputMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

final class MaterialityOmitCommentGeneratorTest extends TestCase
{
    public function testGeneratesACommentForEachOmittedSource(): void
    {
        $comments = (new MaterialityOmitCommentGenerator(new FakeTranslator()))->forDecisions(PropertyCarbonFootprintMother::registered(), [
            MaterialityDecisionInputMother::omit(5, 38.0, 50),
            MaterialityDecisionInputMother::calculate(1),
            MaterialityDecisionInputMother::omit(null),
            MaterialityDecisionInputMother::omit(8),
        ]);

        $this->assertSame([
            Source::OperationsWaste->value => 'Análisis de materialidad: relevancia 38,00 % por debajo del umbral 50 %',
        ], $comments);
    }
}
