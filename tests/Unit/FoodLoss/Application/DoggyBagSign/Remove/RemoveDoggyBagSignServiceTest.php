<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DoggyBagSign\Remove;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignAuthorizer;
use App\FoodLoss\Application\DoggyBagSign\Remove\RemoveDoggyBagSignRequest;
use App\FoodLoss\Application\DoggyBagSign\Remove\RemoveDoggyBagSignService;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRemoved;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveDoggyBagSignServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = DoggyBagSignRepositoryMother::inMemory();

        $doggyBagSign = DoggyBagSignMother::forAccount('account-test');
        $repository->add($doggyBagSign);

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $result = $sut(new RemoveDoggyBagSignRequest($doggyBagSign->id()), $user);

        $this->assertTrue($doggyBagSign->id()->equals($result->doggyBagSignId));

        $removed = $repository->find($doggyBagSign->id());
        $this->assertNull($removed);

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DoggyBagSignRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = DoggyBagSignRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $doggyBagSign = DoggyBagSignMother::registeredDoggyBagSign();

        $this->assertTrue($sut->isHandled(new RemoveDoggyBagSignRequest($doggyBagSign->id())));
    }

    private function createSUT(DoggyBagSignRepository $repository): RemoveDoggyBagSignService
    {
        return new RemoveDoggyBagSignService(
            repository: $repository,
            authorizer: new DoggyBagSignAuthorizer(),
        );
    }
}
