<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\DoggyBagSign\Register;

use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignAuthorizer;
use App\FoodLoss\Application\DoggyBagSign\DoggyBagSignUploader;
use App\FoodLoss\Application\DoggyBagSign\Register\RegisterDoggyBagSignRequest;
use App\FoodLoss\Application\DoggyBagSign\Register\RegisterDoggyBagSignService;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRegistered;
use App\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepository;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\DoggyBagSign\DoggyBagSignRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterDoggyBagSignServiceTest extends TestCase
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

        $sut = $this->createSUT($repository);

        $user = UserMother::fromUsernameAndRoleAndAccount('owner', Role::Owner, new AccountId('account-test'));

        $request = new RegisterDoggyBagSignRequest(
            name: 'Cartel principal',
            location: 'Puerta de entrada',
            accountId: new AccountId('account-test'),
            temporaryFilePath: '/tmp/test.pdf',
            fileExtension: 'pdf',
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->doggyBagSignId);
        $this->assertEquals(36, strlen((string) $result->doggyBagSignId));
        $this->assertEquals('Cartel principal', $result->name);
        $this->assertStringStartsWith('doggy_bag_signs/account-test/', $result->path);

        $persisted = $repository->find($result->doggyBagSignId);

        $this->assertNotNull($persisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof DoggyBagSignRegistered);
        $this->assertEquals('Cartel principal', $lastEvent->name());
        $this->assertTrue($persisted->id()->equals($lastEvent->doggyBagSignId()));
    }

    public function testIsHandle(): void
    {
        $repository = DoggyBagSignRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new RegisterDoggyBagSignRequest(name: 'test')));
    }

    private function createSUT(DoggyBagSignRepository $repository): RegisterDoggyBagSignService
    {
        $fileUploader = new class implements DoggyBagSignUploader {
            public function upload(string $sourcePath, string $extension, AccountId $accountId): string
            {
                return 'doggy_bag_signs/' . $accountId . '/test.' . $extension;
            }
        };

        return new RegisterDoggyBagSignService(
            doggyBagSignRepository: $repository,
            authorizer: new DoggyBagSignAuthorizer(),
            fileUploader: $fileUploader,
        );
    }
}
