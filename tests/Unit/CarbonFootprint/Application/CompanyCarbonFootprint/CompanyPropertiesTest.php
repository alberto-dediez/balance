<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CompanyCarbonFootprint;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyProperties;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

class CompanyPropertiesTest extends TestCase
{
    private CompanyProperties $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $companyId = new CompanyId('company-1');
        $repository = PropertyRepositoryMother::inMemory();
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Zeta', $companyId, new PropertyId('p-1'), OperatingRegime::Owned));
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('alfa', $companyId, new PropertyId('p-2'), OperatingRegime::Franchised));
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Beta', $companyId, new PropertyId('p-3'), OperatingRegime::Managed));
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Delta', $companyId, new PropertyId('p-4'), OperatingRegime::LeasedOut));
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Gamma', $companyId, new PropertyId('p-5'), OperatingRegime::Leased));
        $repository->add(PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Otra', new CompanyId('company-2'), new PropertyId('p-6'), OperatingRegime::Owned));

        $this->sut = new CompanyProperties($repository);
    }

    public function testAllAreSortedByName(): void
    {
        $this->assertSame(['alfa', 'Beta', 'Delta', 'Gamma', 'Zeta'], $this->names($this->sut->all(new CompanyId('company-1'))));
    }

    public function testConsolidatedAndNonConsolidatedSplitByOperatingRegime(): void
    {
        $this->assertSame(['Beta', 'Gamma', 'Zeta'], $this->names($this->sut->consolidated(new CompanyId('company-1'))));
        $this->assertSame(['alfa', 'Delta'], $this->names($this->sut->nonConsolidated(new CompanyId('company-1'))));
    }

    public function testWithoutCompanyReturnsNothing(): void
    {
        $this->assertSame([], $this->sut->all(null));
        $this->assertSame([], $this->sut->consolidated(null));
        $this->assertSame([], $this->sut->nonConsolidated(null));
    }

    /**
     * @param list<Property> $properties
     *
     * @return list<string>
     */
    private function names(array $properties): array
    {
        return array_map(static fn(Property $property): string => $property->name(), $properties);
    }
}
