<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DoggyBagSign\Update;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignAuthorizer;
use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignUploader;
use App\FoodLoss\Application\DoggyBagSign\Update\UpdateDoggyBagSignRequest;
use App\FoodLoss\Application\DoggyBagSign\Update\UpdateDoggyBagSignService;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignUpdated;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignMother;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class UpdateDoggyBagSignServiceTest extends TestCase
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

        $request = new UpdateDoggyBagSignRequest(
            doggyBagSignId: $doggyBagSign->id(),
            name: 'Cartel actualizado',
            location: 'Nueva ubicación',
        );

        $result = $sut($request, $user);

        $this->assertEquals('Cartel actualizado', $result->name);

        $lastEvent = $this->spySubscriber->lastEvent();
        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DoggyBagSignUpdated);
        $this->assertEquals('Cartel actualizado', $lastEvent->name());
    }

    public function testIsHandle(): void
    {
        $repository = DoggyBagSignRepositoryMother::inMemory();
        $sut = $this->createSUT($repository);

        $doggyBagSign = DoggyBagSignMother::registeredDoggyBagSign();

        $this->assertTrue($sut->isHandled(new UpdateDoggyBagSignRequest(doggyBagSignId: $doggyBagSign->id(), name: 'test')));
    }

    private function createSUT(DoggyBagSignRepository $repository): UpdateDoggyBagSignService
    {
        $fileUploader = new class implements DoggyBagSignUploader {
            public function upload(string $sourcePath, string $extension, AccountId $accountId): string
            {
                return 'doggy_bag_signs/' . $accountId . '/updated.' . $extension;
            }
        };

        return new UpdateDoggyBagSignService(
            doggyBagSignRepository: $repository,
            authorizer: new DoggyBagSignAuthorizer(),
            fileUploader: $fileUploader,
        );
    }
}
