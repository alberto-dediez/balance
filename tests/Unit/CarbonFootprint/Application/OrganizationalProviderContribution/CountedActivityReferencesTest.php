<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\CountedActivityReferences;
use App\CarbonFootprint\Domain\Model\Activity\ActivityId;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityPlacement;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\ActivityReference;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryOrganizationalProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintMother;
use PHPUnit\Framework\TestCase;

class CountedActivityReferencesTest extends TestCase
{
    private InMemoryOrganizationalProviderContributionRepository $contributionRepository;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->contributionRepository = OrganizationalProviderContributionRepositoryMother::inMemory();
    }

    public function testKeepsEveryActivityWhenNoContributionIsIgnored(): void
    {
        $footprintId = (string) PropertyCarbonFootprintId::generate();
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('P-1', $footprintId);
        $providerId = ProviderId::generate();
        $this->contributionRepository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: $footprintId, providerId: (string) $providerId));

        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport));
        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $providerId));

        $this->assertCount(2, $this->sut()->of($footprint));
    }

    public function testLeavesOutTheActivitiesOfAnIgnoredContribution(): void
    {
        $footprintId = (string) PropertyCarbonFootprintId::generate();
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('P-1', $footprintId);
        $ignoredProviderId = ProviderId::generate();
        $answeredProviderId = ProviderId::generate();
        $ignored = OrganizationalProviderContributionMother::manual(carbonFootprintId: $footprintId, providerId: (string) $ignoredProviderId);
        $ignored->ignore();
        $this->contributionRepository->add($ignored);
        $this->contributionRepository->add(OrganizationalProviderContributionMother::manual(carbonFootprintId: $footprintId, providerId: (string) $answeredProviderId));

        $ownerActivityId = ActivityId::generate();
        $answeredActivityId = ActivityId::generate();
        $footprint->addActivity($ownerActivityId, new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport));
        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $ignoredProviderId));
        $footprint->addActivity($answeredActivityId, new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $answeredProviderId));

        $this->assertSame(
            [(string) $ownerActivityId, (string) $answeredActivityId],
            $this->activityIds($this->sut()->of($footprint)),
        );
    }

    public function testCountsTheActivitiesAgainOnceTheContributionIsUnignored(): void
    {
        $footprintId = (string) PropertyCarbonFootprintId::generate();
        $footprint = PropertyCarbonFootprintMother::withScope3Answered('P-1', $footprintId);
        $providerId = ProviderId::generate();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: $footprintId, providerId: (string) $providerId);
        $contribution->ignore();
        $this->contributionRepository->add($contribution);
        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $providerId));

        $this->assertCount(0, $this->sut()->of($footprint));

        $contribution->unignore();
        $this->contributionRepository->add($contribution);

        $this->assertCount(1, $this->sut()->of($footprint));
    }

    public function testOnlyLooksAtTheContributionsOfTheSameFootprint(): void
    {
        $footprintId = (string) CompanyCarbonFootprintId::generate();
        $footprint = CompanyCarbonFootprintMother::withScope3Answered('C-1', $footprintId);
        $providerId = ProviderId::generate();
        $ignoredInAnotherFootprint = OrganizationalProviderContributionMother::manual(
            carbonFootprintId: (string) CompanyCarbonFootprintId::generate(),
            providerId: (string) $providerId,
            subjectType: CarbonFootprintReportSubjectType::Company,
        );
        $ignoredInAnotherFootprint->ignore();
        $this->contributionRepository->add($ignoredInAnotherFootprint);
        $ignoredInAPropertyFootprint = OrganizationalProviderContributionMother::manual(carbonFootprintId: $footprintId, providerId: (string) $providerId);
        $ignoredInAPropertyFootprint->ignore();
        $this->contributionRepository->add($ignoredInAPropertyFootprint);

        $footprint->addActivity(ActivityId::generate(), new ActivityPlacement(Scope::Scope3, Source::UpstreamTransport, providerId: $providerId));

        $this->assertCount(1, $this->sut()->of($footprint));

        $ignored = OrganizationalProviderContributionMother::manual(
            carbonFootprintId: $footprintId,
            providerId: (string) $providerId,
            subjectType: CarbonFootprintReportSubjectType::Company,
        );
        $ignored->ignore();
        $this->contributionRepository->add($ignored);

        $this->assertCount(0, $this->sut()->of($footprint));
    }

    /**
     * @param list<ActivityReference> $references
     *
     * @return list<string>
     */
    private function activityIds(array $references): array
    {
        return array_map(static fn(ActivityReference $reference): string => (string) $reference->activityId, $references);
    }

    private function sut(): CountedActivityReferences
    {
        return new CountedActivityReferences($this->contributionRepository);
    }
}
