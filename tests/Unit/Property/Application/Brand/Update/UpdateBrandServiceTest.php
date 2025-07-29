<?php

namespace App\Tests\Unit\Property\Application\Brand\Update;

use PHPUnit\Framework\TestCase;
use App\Security\Domain\Model\User\Role;
use App\Property\Domain\Model\Brand\Brand;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Property\Domain\Model\Brand\BrandUpdated;
use App\Property\Application\Brand\BrandAuthorizer;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Property\Application\Brand\Update\UpdateBrandRequest;
use App\Property\Application\Brand\Update\UpdateBrandService;
use App\Property\Application\Brand\Update\UpdateBrandResponse;
use App\Tests\Unit\Property\Domain\Model\Brand\BrandRepositoryMother;

class UpdateBrandServiceTest extends TestCase
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

        $brand = BrandMother::fromName('MyBrand');

        $repository->add($brand);

        $sut = $this->createSUT($repository);

        $request = new UpdateBrandRequest(
            brandId: $brand->id(),
            name: '1 Estrella'
        );

        $user = UserMother::fromUsernameAndRole('user', Role::Admin);

        $result = $sut($request, $user);

        $this->assertNotNull($result);
        $this->assertTrue($result instanceof UpdateBrandResponse);

        $brandPersisted = $repository->find($result->brandId);

        $this->assertNotNull($brandPersisted);
        $this->assertTrue($brandPersisted instanceof Brand);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof BrandUpdated);
        $this->assertEquals('1 Estrella', $lastEvent->name());
        $this->assertTrue($brandPersisted->id()->equals($lastEvent->brandId()));
    }

    private function createSUT($repository)
    {
        return new UpdateBrandService($repository, new BrandAuthorizer());
    }
}