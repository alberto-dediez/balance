<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\ProviderContribution\Resend;

use App\CarbonFootprint\Application\ProviderContribution\ProviderContributionAuthorizer;
use App\CarbonFootprint\Application\ProviderContribution\Resend\ResendProviderContributionNotificationRequest;
use App\CarbonFootprint\Application\ProviderContribution\Resend\ResendProviderContributionNotificationService;
use App\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionNotificationResent;
use App\CarbonFootprint\Infrastructure\Persistence\InMemory\Repository\InMemoryProviderContributionRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\ProviderContribution\ProviderContributionMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class ResendProviderContributionNotificationServiceTest extends TestCase
{
    private InMemoryProviderContributionRepository $providerContributionRepository;
    private SpyDomainEventSubscriber $spySubscriber;
    private ResendProviderContributionNotificationService $sut;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->providerContributionRepository = new InMemoryProviderContributionRepository();
        $this->spySubscriber = new SpyDomainEventSubscriber();
        $this->sut = new ResendProviderContributionNotificationService($this->providerContributionRepository, new ProviderContributionAuthorizer());
    }

    public function testPublishesNotificationResentEvent(): void
    {
        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $this->providerContributionRepository->add($contribution);

        ($this->sut)(new ResendProviderContributionNotificationRequest($contribution->id()), $this->ownerUser());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(ProviderContributionNotificationResent::class, $lastEvent);
    }

    public function testCannotResendForAnIgnoredContribution(): void
    {
        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $contribution->ignore();
        $this->providerContributionRepository->add($contribution);

        $this->expectException(\InvalidArgumentException::class);
        ($this->sut)(new ResendProviderContributionNotificationRequest($contribution->id()), $this->ownerUser());
    }

    public function testUnauthorizedAccess(): void
    {
        $contribution = ProviderContributionMother::fromEventCarbonFootprintIdAndAccount('event-carbon-footprint-id', 'account-id');
        $this->providerContributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);
        ($this->sut)(new ResendProviderContributionNotificationRequest($contribution->id()), UserMother::fromUsernameAndRole('user', Role::User));
    }

    private function ownerUser(): User
    {
        return UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-id'));
    }
}
