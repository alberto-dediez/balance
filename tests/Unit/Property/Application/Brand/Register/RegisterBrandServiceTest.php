<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Brand\Register;

use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Application\Brand\Register\RegisterBrandRequest;
use App\Property\Application\Brand\Register\RegisterBrandService;
use App\Property\Domain\Model\Brand\BrandRegistered;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterBrandServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = BrandRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new RegisterBrandRequest(
            name: 'Orizonia',
        );

        $result = $sut($request, $user);

        $this->assertNotEmpty((string) $result->brandId);
        $this->assertEquals(36, strlen((string) $result->brandId));

        $brandPersisted = $repository->find($result->brandId);

        $this->assertNotNull($brandPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof BrandRegistered);
        $this->assertEquals('Orizonia', $lastEvent->name());
        $this->assertTrue($brandPersisted->id()->equals($lastEvent->brandId()));
    }

    public function testIsHandle(): void
    {
        $repository = BrandRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterBrandRequest(
            name: 'Travelplan',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryBrandRepository $repository): RegisterBrandService
    {
        return new RegisterBrandService($repository, new BrandAuthorizer());
    }
}
