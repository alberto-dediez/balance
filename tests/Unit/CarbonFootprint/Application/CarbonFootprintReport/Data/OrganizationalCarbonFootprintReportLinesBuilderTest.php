<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\Data;

use App\CarbonFootprint\Application\CarbonFootprintReport\Data\OrganizationalCarbonFootprintReportLinesBuilder;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferencesMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ReportedEmissionsActivityMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class OrganizationalCarbonFootprintReportLinesBuilderTest extends TestCase
{
    use OrganizationalCollectorContextTrait;

    private InMemoryProviderRepository $providerRepository;
    private InMemoryOrganizationalProviderContributionRepository $contributionRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initActivities();
        $this->providerRepository = ProviderRepositoryMother::inMemory();
        $this->contributionRepository = OrganizationalProviderContributionRepositoryMother::inMemory();
    }

    public function testPrefixesTheItemNameWithTheProviderName(): void
    {
        $provider = ProviderMother::fromNameAndAccountAndId('Logística Sur', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        $activity = $this->save(ReportedEmissionsActivityMother::registered(description: 'Reparto 2025'));

        $footprint = PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025);
        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $provider->id()));
        $footprint->addActivity($this->combustibleActivity('180')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $lines = $this->sut()->activityLines($footprint, []);

        $this->assertCount(2, $lines);
        $this->assertStringNotContainsString('·', $lines[0]->itemName);
        $this->assertSame(Source::UpstreamTransport, $lines[1]->source);
        $this->assertSame('Logística Sur · Reparto 2025', $lines[1]->itemName);
    }

    public function testFallsBackToTheProviderIdWhenTheProviderDoesNotExist(): void
    {
        $providerId = ProviderId::generate();
        $activity = $this->save(ReportedEmissionsActivityMother::registered(description: 'Última milla'));

        $footprint = PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025);
        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $providerId));

        $lines = $this->sut()->activityLines($footprint, []);

        $this->assertSame($providerId . ' · Última milla', $lines[0]->itemName);
    }

    public function testPrefixesTheItemNameOfTheEmployeeActivities(): void
    {
        $activity = $this->save(ReportedEmissionsActivityMother::registered(description: 'Tren'));

        $footprint = PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), (string) PropertyCarbonFootprintId::generate(), 'account-id', 2025);
        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope3, Source::EmployeeCommuting, fromEmployees: true));

        $lines = $this->sut()->activityLines($footprint, []);

        $this->assertCount(1, $lines);
        $this->assertSame('Empleados · Tren', $lines[0]->itemName);
    }

    public function testLeavesOutTheActivitiesOfAnIgnoredContribution(): void
    {
        $provider = ProviderMother::fromNameAndAccountAndId('Logística Sur', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);
        $footprintId = (string) PropertyCarbonFootprintId::generate();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: $footprintId, providerId: (string) $provider->id());
        $contribution->ignore();
        $this->contributionRepository->add($contribution);

        $activity = $this->save(ReportedEmissionsActivityMother::registered(description: 'Reparto 2025'));

        $footprint = PropertyCarbonFootprintMother::withScope3Answered((string) PropertyId::generate(), $footprintId, 'account-id', 2025);
        $footprint->addActivity($activity->id(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $provider->id()));
        $footprint->addActivity($this->combustibleActivity('180')->id(), new ActivityPlacement(Scope::Scope1, Source::FixedFacilities));

        $lines = $this->sut()->activityLines($footprint, []);

        $this->assertCount(1, $lines);
        $this->assertSame(Source::FixedFacilities, $lines[0]->source);
    }

    private function sut(): OrganizationalCarbonFootprintReportLinesBuilder
    {
        return new OrganizationalCarbonFootprintReportLinesBuilder(
            $this->activityRepository,
            $this->itemNameResolver(),
            $this->providerRepository,
            CountedActivityReferencesMother::create($this->contributionRepository),
            new FakeTranslator(),
        );
    }
}
