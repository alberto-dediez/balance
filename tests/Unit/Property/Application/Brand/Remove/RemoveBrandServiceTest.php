<?php

namespace App\Tests\Unit\Property\Application\Brand\Remove;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Brand\BrandRemoved;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Brand\Remove\RemoveBrandRequest;
use App\Property\Application\Brand\Remove\RemoveBrandService;
use App\Property\Application\Brand\Remove\RemoveBrandResponse;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;

class RemoveBrandServiceTest extends TestCase
{
    private $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke()
    {
        $repository = BrandRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $brand = BrandMother::random();

        $repository->add($brand);

        $request = new RemoveBrandRequest(
            brandId: $brand->id()
        );

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof RemoveBrandResponse);
        $this->assertTrue($brand->id()->equals($result->brandId));
        
        $brandPersisted = $repository->find($result->brandId);

        $this->assertNull($brandPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof BrandRemoved);
    }

    private function createSUT($repository)
    {
        return new RemoveBrandService($repository, new BrandAuthorizer());
    }
}