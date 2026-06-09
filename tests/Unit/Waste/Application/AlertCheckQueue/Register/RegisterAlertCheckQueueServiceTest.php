<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Application\AlertCheckQueue\Register;

use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueMother;
use App\Tests\Unit\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepositoryMother;
use App\Waste\Application\AlertCheckQueue\AlertCheckQueueAuthorizer;
use App\Waste\Application\AlertCheckQueue\Register\RegisterAlertCheckQueueRequest;
use App\Waste\Application\AlertCheckQueue\Register\RegisterAlertCheckQueueService;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRegistered;
use App\Waste\Domain\Model\AlertCheckQueue\AlertCheckQueueRepository;
use PHPUnit\Framework\TestCase;

class RegisterAlertCheckQueueServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = AlertCheckQueueRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $accountId = new AccountId('account-id-test');
        $checkDate = new \DateTimeImmutable('2024-03-01');

        $result = $sut(new RegisterAlertCheckQueueRequest($accountId, $checkDate), UserMother::adminUser());

        $this->assertNotNull($result->alertCheckQueueId);

        $persisted = $repository->find($result->alertCheckQueueId);
        $this->assertNotNull($persisted);
        $this->assertTrue($accountId->equals($persisted->accountId()));
        $this->assertEquals($checkDate->format('Y-m-d'), $persisted->checkDate()->format('Y-m-d'));

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertInstanceOf(AlertCheckQueueRegistered::class, $lastEvent);
    }

    public function testDoesNotCreateDuplicateWhenEntryAlreadyExists(): void
    {
        $repository = AlertCheckQueueRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $accountId = new AccountId('account-id-test');
        $checkDate = new \DateTimeImmutable('2024-03-01');

        $existing = AlertCheckQueueMother::forAccountAndDate($accountId, $checkDate);
        $repository->add($existing);

        $eventCountBeforeSut = count($this->spySubscriber->events());

        $result = $sut(new RegisterAlertCheckQueueRequest($accountId, $checkDate), UserMother::adminUser());

        $this->assertNull($result->alertCheckQueueId);
        $this->assertCount(1, $repository->findAll());
        $this->assertCount($eventCountBeforeSut, $this->spySubscriber->events());
    }

    public function testIsHandle(): void
    {
        $sut = $this->createSUT(AlertCheckQueueRepositoryMother::inMemory());

        $this->assertTrue($sut->isHandled(new RegisterAlertCheckQueueRequest(
            accountId: new AccountId('account-id-test'),
            checkDate: new \DateTimeImmutable('2024-03-01'),
        )));
    }

    private function createSUT(AlertCheckQueueRepository $repository): RegisterAlertCheckQueueService
    {
        return new RegisterAlertCheckQueueService(
            alertCheckQueueRepository: $repository,
            authorizer: new AlertCheckQueueAuthorizer(),
        );
    }
}
