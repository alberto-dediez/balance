<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyIntensityDenominatorsDefaults;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\IntensityDenominators;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryPropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepositoryMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

final class CompanyIntensityDenominatorsDefaultsTest extends TestCase
{
    private InMemoryPropertyCarbonFootprintRepository $footprints;
    private CompanyIntensityDenominatorsDefaults $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $companyId = new CompanyId('company-1');
        $properties = PropertyRepositoryMother::inMemory();
        $properties->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Uno', $companyId, new PropertyId('p-1'), OperatingRegime::Owned));
        $properties->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Dos', $companyId, new PropertyId('p-2'), OperatingRegime::Managed));
        $properties->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Tres', $companyId, new PropertyId('p-3'), OperatingRegime::Franchised));

        $this->footprints = PropertyCarbonFootprintRepositoryMother::inMemory();
        $this->sut = new CompanyIntensityDenominatorsDefaults(new CompanyProperties($properties), $this->footprints);
    }

    public function testSumsOnlyConsolidatedPropertiesOfTheSameYear(): void
    {
        $this->addPropertyFootprint('p-1', 'f-1', 2025, new IntensityDenominators(stays: 41000, builtAreaM2: '2000.00'));
        $this->addPropertyFootprint('p-2', 'f-2', 2025, new IntensityDenominators(stays: 12000));
        $this->addPropertyFootprint('p-3', 'f-3', 2025, new IntensityDenominators(stays: 99999));
        $this->addPropertyFootprint('p-1', 'f-4', 2024, new IntensityDenominators(stays: 55555));

        $defaults = $this->sut->for(CompanyCarbonFootprintMother::from('company-1', 'cf-1', 'account-id', 2025));

        $this->assertSame(53000, $defaults->stays());
        $this->assertSame('2000.00', $defaults->builtAreaM2());
        $this->assertNull($defaults->revenueEur());
    }

    public function testIgnoresRemovedFootprints(): void
    {
        $removed = $this->addPropertyFootprint('p-1', 'f-1', 2025, new IntensityDenominators(stays: 41000));
        $removed->remove();
        $this->addPropertyFootprint('p-2', 'f-2', 2025, new IntensityDenominators(stays: 12000));

        $defaults = $this->sut->for(CompanyCarbonFootprintMother::from('company-1', 'cf-1', 'account-id', 2025));

        $this->assertSame(12000, $defaults->stays());
    }

    public function testWithoutFootprintsIsEmpty(): void
    {
        $this->assertTrue($this->sut->for(CompanyCarbonFootprintMother::from('company-1', 'cf-1', 'account-id', 2025))->isEmpty());
    }

    private function addPropertyFootprint(string $propertyId, string $id, int $year, IntensityDenominators $denominators): PropertyCarbonFootprint
    {
        $footprint = PropertyCarbonFootprintMother::from($propertyId, $id, 'account-id', $year);
        $footprint->declareIntensityDenominators($denominators);
        $this->footprints->add($footprint);

        return $footprint;
    }
}
