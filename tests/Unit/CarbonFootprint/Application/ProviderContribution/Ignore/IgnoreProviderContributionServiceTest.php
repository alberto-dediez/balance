<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\Ignore;

use App\CarbonFootprint\Application\ProviderContribution\Ignore\IgnoreProviderContributionRequest;
use App\CarbonFootprint\Application\ProviderContribution\Ignore\IgnoreProviderContributionService;
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

class IgnoreProviderContributionServiceTest extends TestCase
{
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private IgnoreProviderContributionService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->sut = new IgnoreProviderContributionService($this->providerContributionRepository, new ProviderContributionAuthorizer());
    }

    public function testIgnoresTheContribution(): void
    {
        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $this->providerContributionRepository->add($contribution);

        ($this->sut)(new IgnoreProviderContributionRequest($contribution->id()), $this->ownerUser());

        $persisted = $this->providerContributionRepository->find($contribution->id());
        $this->assertNotNull($persisted);
        $this->assertTrue($persisted->status()->isIgnored());
    }

    public function testProviderContributionNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new IgnoreProviderContributionRequest(ProviderContributionMother::random()->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $this->providerContributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(new IgnoreProviderContributionRequest($contribution->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
