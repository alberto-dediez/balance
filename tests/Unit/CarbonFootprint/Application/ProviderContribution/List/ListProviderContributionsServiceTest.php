<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\List;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\ProviderContribution\List\ListProviderContributionsRequest;
use App\CarbonFootprint\Application\ProviderContribution\List\ListProviderContributionsService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class ListProviderContributionsServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private ListProviderContributionsService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->providerContributionRepository = new InMemoryProviderContributionRepository();

        $this->sut = new ListProviderContributionsService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->providerContributionRepository,
        );
    }

    public function testListsContributionsForTheEventCarbonFootprint(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount((string) $eventCarbonFootprint->id(), 'account-id');
        $this->providerContributionRepository->add($contribution);

        $otherContribution = ProviderContributionMother::registeredProviderContribution();
        $this->providerContributionRepository->add($otherContribution);

        $response = ($this->sut)(new ListProviderContributionsRequest($eventCarbonFootprint->id()), $this->ownerUser());

        $this->assertCount(1, $response->providerContributions);
        $this->assertTrue($response->providerContributions[0]->id()->equals($contribution->id()));
    }

    public function testUnauthorizedAccess(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(
            new ListProviderContributionsRequest($eventCarbonFootprint->id()),
            UserMother::fromUsernameAndRole('user', Role::User),
        );
    }

    private function registeredEventCarbonFootprint(): EventCarbonFootprint
    {
        $eventCarbonFootprint = EventCarbonFootprintMother::fromEventIdAndAccount('event-id', 'account-id');
        $this->eventCarbonFootprintRepository->add($eventCarbonFootprint);

        return $eventCarbonFootprint;
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
