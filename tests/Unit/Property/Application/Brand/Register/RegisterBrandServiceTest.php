<?php

namespace App\Tests\Unit\Property\Application\Brand\Register;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Brand\Brand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Domain\Model\Brand\BrandRegistered;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Brand\Register\RegisterBrandRequest;
use App\Property\Application\Brand\Register\RegisterBrandService;
use App\Property\Application\Brand\Register\RegisterBrandResponse;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;

class RegisterBrandServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase()
    {
        $repository = BrandRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('admin', Role::Admin);

        $sut = $this->createSUT($repository);

        $request = new RegisterBrandRequest(
            name: 'Orizonia'
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RegisterBrandResponse);
        $this->assertNotEmpty((string) $result->brandId);
        $this->assertEquals(36, strlen((string) $result->brandId));

        $brandPersisted = $repository->find($result->brandId);

        $this->assertNotNull($brandPersisted);
        $this->assertTrue($brandPersisted instanceof Brand);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof BrandRegistered);
        $this->assertEquals('Orizonia', $lastEvent->name());
        $this->assertTrue($brandPersisted->id()->equals($lastEvent->brandId()));
    }

    private function createSUT($repository)
    {
        return new RegisterBrandService($repository, new BrandAuthorizer());
    }
}