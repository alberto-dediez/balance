<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\Resend;

use App\CarbonFootprint\Application\OperatorContribution\Resend\ResendOperatorContributionNotificationRequest;
use App\CarbonFootprint\Application\OperatorContribution\Resend\ResendOperatorContributionNotificationService;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionClosedException;
use App\CarbonFootprint\Domain\Model\OperatorContribution\OperatorContributionNotificationResent;
use App\Tests\Unit\CarbonFootprint\Application\OperatorContribution\OperatorContributionServiceTestCase;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;

class ResendOperatorContributionNotificationServiceTest extends OperatorContributionServiceTestCase
{
    public function testResendsTheNotification(): void
    {
        $contribution = $this->contribution($this->companyFootprint());
        $spySubscriber = new SpyDomainEventSubscriber();

        $this->createSUT()(new ResendOperatorContributionNotificationRequest($contribution->id()), $this->owner());

        $this->assertInstanceOf(OperatorContributionNotificationResent::class, $spySubscriber->lastEvent());
    }

    public function testACompletedFootprintIsClosed(): void
    {
        $contribution = $this->contribution($this->completedCompanyFootprint());

        $this->expectException(OperatorContributionClosedException::class);

        $this->createSUT()(new ResendOperatorContributionNotificationRequest($contribution->id()), $this->owner());
    }

    private function createSUT(): ResendOperatorContributionNotificationService
    {
        return new ResendOperatorContributionNotificationService($this->contributionRepository, $this->companyCarbonFootprintRepository, $this->companyCarbonFootprintAuthorizer, $this->authorizer);
    }
}
