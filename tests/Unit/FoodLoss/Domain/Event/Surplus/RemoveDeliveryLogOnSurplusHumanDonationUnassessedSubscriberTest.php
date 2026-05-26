<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\Surplus;

use App\FoodLoss\Application\DeliveryLog\DeliveryLogAuthorizer;
use App\FoodLoss\Application\DeliveryLog\Remove\RemoveDeliveryLogService;
use App\FoodLoss\Domain\Event\Surplus\RemoveDeliveryLogOnSurplusHumanDonationUnassessedSubscriber;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogFilter;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogId;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRemoved;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepository;
use App\FoodLoss\Domain\Model\Surplus\SurplusHumanDonationUnassessed;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Security\Infrastructure\Symfony\Security\FakeLoggedUser;
use PHPUnit\Framework\TestCase;

class RemoveDeliveryLogOnSurplusHumanDonationUnassessedSubscriberTest extends TestCase
{
    private RemoveDeliveryLogOnSurplusHumanDonationUnassessedSubscriber $sut;
    private DeliveryLogRepository $deliveryLogRepository;
    private SurplusId $surplusId;
    private SurplusHumanDonationUnassessed $event;
    private AccountId $accountId;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->accountId = new AccountId('account-id-test');
        $this->surplusId = SurplusId::generate();
        $this->event = new SurplusHumanDonationUnassessed($this->surplusId);
        $this->deliveryLogRepository = DeliveryLogRepositoryMother::inMemory();
        $this->sut = $this->createSUT();
    }

    public function testIsSubscribedToSurplusHumanDonationUnassessed(): void
    {
        $this->assertTrue($this->sut->isSubscribedTo($this->event));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $this->assertFalse($this->sut->isSubscribedTo(new DeliveryLogRemoved(DeliveryLogId::generate())));
    }

    public function testRemovesDeliveryLogWhenItExistsForSurplus(): void
    {
        $deliveryLog = DeliveryLogMother::withSurplusId($this->surplusId, $this->accountId);
        $this->deliveryLogRepository->add($deliveryLog);

        $this->sut->handle($this->event);

        $this->assertCount(0, $this->deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    public function testDoesNothingWhenNoDeliveryLogExistsForSurplus(): void
    {
        $this->deliveryLogRepository->add(DeliveryLogMother::random());

        $this->sut->handle($this->event);

        $this->assertCount(1, $this->deliveryLogRepository->findByFilter(new DeliveryLogFilter(limit: 0)));
    }

    private function createSUT(): RemoveDeliveryLogOnSurplusHumanDonationUnassessedSubscriber
    {
        $authorizer = new DeliveryLogAuthorizer();

        return new RemoveDeliveryLogOnSurplusHumanDonationUnassessedSubscriber(
            deliveryLogRepository: $this->deliveryLogRepository,
            removeDeliveryLogService: new RemoveDeliveryLogService(
                repository: $this->deliveryLogRepository,
                authorizer: $authorizer,
            ),
            loggedUser: new FakeLoggedUser(
                UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, $this->accountId),
            ),
        );
    }
}
