<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\Register;

use App\CarbonFootprint\Application\EventCarbonFootprint\EventCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\ProviderContribution\Register\RegisterProviderContributionRequest;
use App\CarbonFootprint\Application\ProviderContribution\Register\RegisterProviderContributionService;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprint;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryEventCarbonFootprintRepository;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EventCarbonFootprint\EventCarbonFootprintMother;
use App\Tests\Unit\EventOperations\Domain\Model\Provider\ProviderMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class RegisterProviderContributionServiceTest extends TestCase
{
    private InMemoryEventCarbonFootprintRepository $eventCarbonFootprintRepository;
    private InMemoryProviderRepository $providerRepository;
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private RegisterProviderContributionService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->eventCarbonFootprintRepository = new InMemoryEventCarbonFootprintRepository();
        $this->providerRepository = new InMemoryProviderRepository();
        $this->providerContributionRepository = new InMemoryProviderContributionRepository();

        $this->sut = new RegisterProviderContributionService(
            $this->eventCarbonFootprintRepository,
            new EventCarbonFootprintAuthorizer(),
            $this->providerRepository,
            $this->providerContributionRepository,
        );
    }

    public function testRegistersTheContributionAndSnapshotsTheProviderName(): void
    {
        $eventCarbonFootprint = $this->registeredEventCarbonFootprint();
        $provider = ProviderMother::fromNameAndAccountAndId('Acme Catering', 'account-id', (string) ProviderId::generate());
        $this->providerRepository->add($provider);

        $response = ($this->sut)(
            new RegisterProviderContributionRequest($eventCarbonFootprint->id(), $provider->id()),
            $this->ownerUser(),
        );

        $providerContribution = $this->providerContributionRepository->find($response->providerContributionId);
        $this->assertNotNull($providerContribution);
        $this->assertSame('Acme Catering', $providerContribution->companyName());
        $providerId = $providerContribution->providerId();
        $this->assertNotNull($providerId);
        $this->assertTrue($providerId->equals($provider->id()));
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
