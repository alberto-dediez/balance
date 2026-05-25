<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\Surplus\Assess\HumanDonation;

use App\FoodLoss\Application\Surplus\Assess\HumanDonation\AssessHumanDonationRequest;
use App\FoodLoss\Application\Surplus\Assess\HumanDonation\AssessHumanDonationService;
use App\FoodLoss\Application\Surplus\SurplusAuthorizer;
use App\FoodLoss\Domain\Model\Surplus\SurplusDestination;
use App\FoodLoss\Domain\Model\Surplus\SurplusHumanDonationAssessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusMother;
use App\Tests\Unit\FoodLoss\Domain\Model\Surplus\SurplusRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class AssessHumanDonationServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testAssessHumanDonationYesSetsDestination(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAllAssessmentsAnsweredNo($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessHumanDonationRequest($surplus->id(), true, null),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertSame(SurplusDestination::HumanDonation, $updated->destination());
        $this->assertTrue($updated->humanDonationAssessment()->canBeHumanDonated());
        $this->assertFalse($updated->animalFeedAssessment()->isAnswered());
        $this->assertFalse($updated->compostingAssessment()->isAnswered());
        $this->assertFalse($updated->eliminationAssessment()->isAnswered());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusHumanDonationAssessed::class, $lastEvent);
        $this->assertTrue($lastEvent->canBeHumanDonated());
    }

    public function testAssessHumanDonationNoDoesNotSetDestination(): void
    {
        $accountId = new AccountId('account-id-test');
        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::withAccountId($accountId);
        $repository->add($surplus);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $accountId);
        $this->createSUT($repository)(
            new AssessHumanDonationRequest($surplus->id(), false, null),
            $user,
        );

        $updated = $repository->find($surplus->id());

        $this->assertNotNull($updated);
        $this->assertNull($updated->destination());
        $this->assertFalse($updated->humanDonationAssessment()->canBeHumanDonated());

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertInstanceOf(SurplusHumanDonationAssessed::class, $lastEvent);
        $this->assertFalse($lastEvent->canBeHumanDonated());
    }

    public function testThrowsWhenSurplusNotFound(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $repository = SurplusRepositoryMother::inMemory();
        $surplus = SurplusMother::random();

        $user = UserMother::adminUser();
        $this->createSUT($repository)(
            new AssessHumanDonationRequest($surplus->id(), true, null),
            $user,
        );
    }

    private function createSUT(SurplusRepository $repository): AssessHumanDonationService
    {
        return new AssessHumanDonationService(
            surplusRepository: $repository,
            authorizer: new SurplusAuthorizer(),
        );
    }
}
