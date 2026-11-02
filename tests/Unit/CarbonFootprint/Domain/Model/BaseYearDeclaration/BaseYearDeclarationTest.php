<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\BaseYearDeclaration;

use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclaration;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationId;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRemoved;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclared;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityIndicator;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

final class BaseYearDeclarationTest extends TestCase
{
    public function testDeclarePublishesTheEventAndSetsTheState(): void
    {
        $declaration = BaseYearDeclarationMother::declared(year: 2023, total: '1500.50', primaryIndicator: IntensityIndicator::PerStay);

        $this->assertCount(1, $this->eventsOf($declaration, BaseYearDeclared::class));
        $this->assertSame(2023, $declaration->year());
        $this->assertSame('1500.50', $declaration->baseYearTotalKgCo2eq());
        $this->assertSame(IntensityIndicator::PerStay, $declaration->primaryIndicator());
        $this->assertSame('account-1', (string) $declaration->accountId());
    }

    public function testEmptyJustificationThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->declare(justification: '   ');
    }

    public function testZeroThresholdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->declare(threshold: '0');
    }

    public function testThresholdAboveOneHundredThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->declare(threshold: '100.01');
    }

    public function testRedeclarePublishesAgainAndUpdatesTheSnapshot(): void
    {
        $declaration = BaseYearDeclarationMother::declared(year: 2023, total: '100');

        $declaration->redeclare(2024, 'Cambio de perímetro', '10', null, '250');

        $this->assertCount(2, $this->eventsOf($declaration, BaseYearDeclared::class));
        $this->assertSame(2024, $declaration->year());
        $this->assertSame('250', $declaration->baseYearTotalKgCo2eq());
        $this->assertSame('10', $declaration->recalculationThresholdPercent());
        $this->assertSame('Cambio de perímetro', $declaration->justification());
    }

    public function testExceedsThreshold(): void
    {
        $declaration = BaseYearDeclarationMother::declared(total: '100', threshold: '5');

        $this->assertFalse($declaration->exceedsThreshold(105.0));
        $this->assertTrue($declaration->exceedsThreshold(106.0));
        $this->assertTrue($declaration->exceedsThreshold(94.0));
    }

    public function testRemovePublishesTheEvent(): void
    {
        $declaration = BaseYearDeclarationMother::declared();

        $declaration->remove();

        $this->assertCount(1, $this->eventsOf($declaration, BaseYearDeclarationRemoved::class));
    }

    private function declare(string $justification = 'Justificación', string $threshold = '5'): BaseYearDeclaration
    {
        return BaseYearDeclaration::declare(
            BaseYearDeclarationId::generate(),
            CarbonFootprintReportSubjectType::Property,
            'property-1',
            new AccountId('account-1'),
            2024,
            $justification,
            $threshold,
            null,
            '100',
        );
    }

    /**
     * @param class-string $class
     *
     * @return list<object>
     */
    private function eventsOf(BaseYearDeclaration $declaration, string $class): array
    {
        return array_values(array_filter($declaration->recordedEvents(), static fn(object $event): bool => $event instanceof $class));
    }
}
