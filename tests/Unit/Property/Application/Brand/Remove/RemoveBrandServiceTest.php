<?php

declare(strict_types=1);

namespace App\Tests\Unit\Property\Application\Brand\Remove;

use App\Property\Application\Brand\BrandAuthorizer;
use App\Property\Application\Brand\Remove\RemoveBrandRequest;
use App\Property\Application\Brand\Remove\RemoveBrandResponse;
use App\Property\Application\Brand\Remove\RemoveBrandService;
use App\Property\Domain\Model\Brand\BrandRemoved;
use App\Property\Infrastructure\Persistence\InMemory\Repository\InMemoryBrandRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandMother;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveBrandServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = BrandRepositoryMother::inMemory();

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $sut = $this->createSUT($repository);

        $brand = BrandMother::random();

        $repository->add($brand);

        $request = new RemoveBrandRequest(
            brandId: $brand->id(),
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

    public function testIsHandle(): void
    {
        $repository = BrandRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $brand = BrandMother::random();

        $result = $sut->isHandled(new RemoveBrandRequest(
            brandId: $brand->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(InMemoryBrandRepository $repository): RemoveBrandService
    {
        return new RemoveBrandService($repository, new BrandAuthorizer());
    }
}
