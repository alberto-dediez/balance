<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\ProviderContributionMode;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionStatus;
use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use PHPUnit\Framework\TestCase;

final class ProviderContributionsReportBuilderTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initActivities();
    }

    public function testAnAnsweredManualContributionCarriesTheEmissionsOfTheProvider(): void
    {
        $footprint = $this->footprint();
        $provider = $this->provider('Lavandería Norte');
        $footprint->addActivity($this->transportActivity('300')->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $provider->id()));
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id(), providerId: (string) $provider->id());
        $contribution->answer();
        $this->providerContributions->add($contribution);

        $lines = $this->providerContributionsReportBuilder()->build($footprint, CarbonFootprintReportSubjectType::Property, []);

        $this->assertCount(1, $lines);
        $this->assertSame('Lavandería Norte', $lines[0]->providerName);
        $this->assertSame(ProviderContributionMode::Manual, $lines[0]->mode);
        $this->assertSame(ProviderContributionStatus::Answered, $lines[0]->status);
        $this->assertNotNull($lines[0]->answeredAt);
        $this->assertSame([Source::UpstreamTransport], $lines[0]->sources);
        $this->assertSame(300.0, (float) $lines[0]->emissionsCo2eq);
        $this->assertNull($lines[0]->propertyName);
    }

    public function testARequestedPendingContributionHasNoAnswerDate(): void
    {
        $footprint = $this->footprint();
        $provider = $this->provider('Transportes Sur');
        $this->providerContributions->add(OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id(), providerId: (string) $provider->id()));

        $lines = $this->providerContributionsReportBuilder()->build($footprint, CarbonFootprintReportSubjectType::Property, []);

        $this->assertSame(ProviderContributionMode::Requested, $lines[0]->mode);
        $this->assertSame(ProviderContributionStatus::Pending, $lines[0]->status);
        $this->assertNull($lines[0]->answeredAt);
    }

    public function testAnIgnoredContributionIsListedWithoutEmissions(): void
    {
        $footprint = $this->footprint();
        $provider = $this->provider('Catering Este');
        $footprint->addActivity($this->transportActivity('300')->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $provider->id()));
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id(), providerId: (string) $provider->id());
        $contribution->ignore();
        $this->providerContributions->add($contribution);

        $lines = $this->providerContributionsReportBuilder()->build($footprint, CarbonFootprintReportSubjectType::Property, []);

        $this->assertSame(ProviderContributionStatus::Ignored, $lines[0]->status);
        $this->assertSame('0', $lines[0]->emissionsCo2eq);
    }

    public function testARemovedProviderIsNamedByItsIdentifier(): void
    {
        $footprint = $this->footprint();
        $providerId = (string) ProviderId::generate();
        $this->providerContributions->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id(), providerId: $providerId));

        $lines = $this->providerContributionsReportBuilder()->build($footprint, CarbonFootprintReportSubjectType::Property, []);

        $this->assertSame($providerId, $lines[0]->providerName);
    }

    public function testTheContributionsAreSortedByStatusAndName(): void
    {
        $footprint = $this->footprint();
        $ignored = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id(), providerId: (string) $this->provider('Alfa')->id());
        $ignored->ignore();
        $answered = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id(), providerId: (string) $this->provider('Zeta')->id());
        $answered->answer();

        foreach ([
            $ignored,
            OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id(), providerId: (string) $this->provider('Omega')->id()),
            OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id(), providerId: (string) $this->provider('Beta')->id()),
            $answered,
        ] as $contribution) {
            $this->providerContributions->add($contribution);
        }

        $lines = $this->providerContributionsReportBuilder()->build($footprint, CarbonFootprintReportSubjectType::Property, []);

        $this->assertSame(['Zeta', 'Beta', 'Omega', 'Alfa'], array_map(static fn($line): string => $line->providerName, $lines));
    }

    public function testACompanyContributionCarriesThePropertyName(): void
    {
        $propertyId = (string) PropertyId::generate();
        $footprint = CompanyCarbonFootprintMother::withEmployeesAnswered('company-id', (string) CompanyCarbonFootprintId::generate(), 'account-id', 2025);
        $this->providerContributions->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id(), providerId: (string) $this->provider('Lavandería Norte')->id(), subjectType: CarbonFootprintReportSubjectType::Company, propertyId: $propertyId));

        $lines = $this->providerContributionsReportBuilder()->build($footprint, CarbonFootprintReportSubjectType::Company, [$propertyId => 'Hotel Sol']);

        $this->assertSame('Hotel Sol', $lines[0]->propertyName);
    }

    private function footprint(): PropertyCarbonFootprint
    {
        return PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025);
    }

    private function provider(string $name): Provider
    {
        $provider = ProviderMother::fromNameAndAccountAndId($name, 'account-id', (string) ProviderId::generate());
        $this->providers->add($provider);

        return $provider;
    }
}
