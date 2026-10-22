<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Domain\Event\Company;

use App\Property\Domain\Event\Company\UnlinkCompanyOnCompanyRemovedSubscriber;
use App\Property\Domain\Model\Company\CompanyId;
use App\Property\Domain\Model\Company\CompanyRemoved;
use App\Property\Domain\Model\Property\OperatingRegime;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use PHPUnit\Framework\TestCase;

class UnlinkCompanyOnCompanyRemovedSubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToCompanyRemoved(): void
    {
        $sut = new UnlinkCompanyOnCompanyRemovedSubscriber(PropertyRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new CompanyRemoved(CompanyId::generate())));
    }

    public function testHandleUnlinksTheCompanyAndKeepsTheOperatingRegimeAndFigures(): void
    {
        $repository = PropertyRepositoryMother::inMemory();
        $companyId = CompanyId::generate();

        $property = PropertyMother::fromNameAndCompanyIdAndIdAndRegime('Hotel', $companyId, PropertyId::generate(), OperatingRegime::Franchised);
        $property->update(
            name: 'Hotel',
            accountId: null,
            companyId: $companyId,
            numberOfEmployees: 12,
            dailyClients: 80,
            operatingRegime: OperatingRegime::Franchised,
        );
        $repository->add($property);

        $sut = new UnlinkCompanyOnCompanyRemovedSubscriber($repository);
        $sut->handle(new CompanyRemoved($companyId));

        $unlinked = $repository->find($property->id());
        $this->assertNotNull($unlinked);
        $this->assertNull($unlinked->companyId());
        $this->assertSame(OperatingRegime::Franchised, $unlinked->operatingRegime());
        $this->assertSame(12, $unlinked->numberOfEmployees());
        $this->assertSame(80, $unlinked->dailyClients());
    }
}
