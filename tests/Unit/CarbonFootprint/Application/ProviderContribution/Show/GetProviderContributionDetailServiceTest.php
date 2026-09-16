<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\Show;

use App\CarbonFootprint\Application\Activity\Catalog\ActivityCatalogProviderRegistry;
use App\CarbonFootprint\Application\Activity\Catalog\CombustibleCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MachineryFuelCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\MobilityCatalogProvider;
use App\CarbonFootprint\Application\Activity\Catalog\OvernightStayCatalogProvider;
use App\CarbonFootprint\Application\CarbonFootprint\StageSectionsBuilder;
use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthorizer;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionPhaseSectionsBuilder;
use App\CarbonFootprint\Application\ProviderContribution\Show\GetProviderContributionDetailRequest;
use App\CarbonFootprint\Application\ProviderContribution\Show\GetProviderContributionDetailService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\Phase;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Activity\ActivityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Mobility\MobilityRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class GetProviderContributionDetailServiceTest extends TestCase
{
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private GetProviderContributionDetailService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();

        $catalogProviders = new ActivityCatalogProviderRegistry([
            new CombustibleCatalogProvider(CombustibleRepositoryMother::inMemory()),
            new MachineryFuelCatalogProvider(MachineryFuelRepositoryMother::inMemory()),
            new OvernightStayCatalogProvider(OvernightStayRepositoryMother::inMemory()),
            new MobilityCatalogProvider(MobilityRepositoryMother::inMemory()),
        ]);

        $this->sut = new GetProviderContributionDetailService(
            $this->providerContributionRepository,
            new ProviderContributionAuthorizer(),
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            new ProviderContributionPhaseSectionsBuilder(new StageSectionsBuilder($catalogProviders, ActivityRepositoryMother::inMemory())),
        );
    }

    public function testReturnsSectionsForTheThreePhasesTheProviderCanAnswer(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $this->providerContributionRepository->add($contribution);

        $response = ($this->sut)(new GetProviderContributionDetailRequest($contribution->id()), $this->ownerUser());

        $this->assertTrue($response->providerContribution->id()->equals($contribution->id()));
        $this->assertArrayHasKey(Phase::Assembly->value, $response->sectionsByPhase);
        $this->assertArrayHasKey(Phase::Celebration->value, $response->sectionsByPhase);
        $this->assertArrayHasKey(Phase::Breakdown->value, $response->sectionsByPhase);
        $this->assertArrayNotHasKey(Phase::Materials->value, $response->sectionsByPhase);
    }

    public function testProviderContributionNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new GetProviderContributionDetailRequest(ProviderContributionMother::random()->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $this->providerContributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(new GetProviderContributionDetailRequest($contribution->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
