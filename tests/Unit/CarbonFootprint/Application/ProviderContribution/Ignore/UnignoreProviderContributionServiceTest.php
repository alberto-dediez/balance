<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\Ignore;

use App\CarbonFootprint\Application\ProviderContribution\Ignore\UnignoreProviderContributionRequest;
use App\CarbonFootprint\Application\ProviderContribution\Ignore\UnignoreProviderContributionService;
use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthorizer;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UnignoreProviderContributionServiceTest extends TestCase
{
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private UnignoreProviderContributionService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->sut = new UnignoreProviderContributionService($this->providerContributionRepository, new ProviderContributionAuthorizer());
    }

    public function testUnignoresTheContribution(): void
    {
        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $contribution->ignore();
        $this->providerContributionRepository->add($contribution);

        ($this->sut)(new UnignoreProviderContributionRequest($contribution->id()), $this->ownerUser());

        $persisted = $this->providerContributionRepository->find($contribution->id());
        $this->assertNotNull($persisted);
        $this->assertFalse($persisted->status()->isIgnored());
    }

    public function testUnauthorizedAccess(): void
    {
        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $contribution->ignore();
        $this->providerContributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(new UnignoreProviderContributionRequest($contribution->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
