<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\Resend;

use App\CarbonFootprint\Application\OrganizationalProviderContribution\Resend\ResendOrganizationalProviderContributionNotificationRequest;
use App\CarbonFootprint\Application\OrganizationalProviderContribution\Resend\ResendOrganizationalProviderContributionNotificationService;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionClosedException;
use App\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionNotificationResent;
use App\Security\Application\UnauthorizedActionException;
use App\Tests\Unit\CarbonFootprint\Application\OrganizationalProviderContribution\OrganizationalProviderContributionServiceTestCase;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OrganizationalProviderContribution\OrganizationalProviderContributionMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;

class ResendOrganizationalProviderContributionNotificationServiceTest extends OrganizationalProviderContributionServiceTestCase
{
    private ResendOrganizationalProviderContributionNotificationService $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new ResendOrganizationalProviderContributionNotificationService($this->contributionRepository, $this->authorizer, $this->locator);
    }

    public function testResendsRequestedContribution(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);
        $spySubscriber = new SpyDomainEventSubscriber();

        ($this->sut)(new ResendOrganizationalProviderContributionNotificationRequest($contribution->id()), $this->owner());

        $this->assertInstanceOf(OrganizationalProviderContributionNotificationResent::class, $spySubscriber->lastEvent());
    }

    public function testManualContributionThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::manual(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(\LogicException::class);

        ($this->sut)(new ResendOrganizationalProviderContributionNotificationRequest($contribution->id()), $this->owner());
    }

    public function testIgnoredContributionThrows(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $contribution->ignore();
        $this->contributionRepository->add($contribution);

        $this->expectException(\LogicException::class);

        ($this->sut)(new ResendOrganizationalProviderContributionNotificationRequest($contribution->id()), $this->owner());
    }

    public function testCompletedFootprintThrows(): void
    {
        $footprint = $this->completedPropertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(OrganizationalProviderContributionClosedException::class);

        ($this->sut)(new ResendOrganizationalProviderContributionNotificationRequest($contribution->id()), $this->owner());
    }

    public function testUserIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ResendOrganizationalProviderContributionNotificationRequest($contribution->id()), $this->plainUser());
    }

    public function testOwnerOfAnotherAccountIsDenied(): void
    {
        $footprint = $this->propertyFootprint();
        $contribution = OrganizationalProviderContributionMother::requested(carbonFootprintId: (string) $footprint->id());
        $this->contributionRepository->add($contribution);

        $this->expectException(UnauthorizedActionException::class);

        ($this->sut)(new ResendOrganizationalProviderContributionNotificationRequest($contribution->id()), $this->owner('other-account'));
    }
}
