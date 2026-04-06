<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Domain\Event\ServiceCategory;

use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Domain\Event\ServiceCategory\UnlinkServiceCategorySubscriber;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRemoved;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRemoved;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointMother;
use App\Tests\Unit\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepositoryMother;
use PHPUnit\Framework\TestCase;

class UnlinkServiceCategorySubscriberTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testIsSubscribedToServiceCategoryRemoved(): void
    {
        $sut = new UnlinkServiceCategorySubscriber(CriticalPointRepositoryMother::inMemory());

        $this->assertTrue($sut->isSubscribedTo(new ServiceCategoryRemoved(ServiceCategoryId::generate())));
    }

    public function testIsNotSubscribedToOtherEvents(): void
    {
        $sut = new UnlinkServiceCategorySubscriber(CriticalPointRepositoryMother::inMemory());

        $this->assertFalse($sut->isSubscribedTo(new CriticalPointRemoved(CriticalPointId::generate())));
    }

    public function testHandleUnlinksServiceCategoryFromAllLinkedCriticalPoints(): void
    {
        $repository = CriticalPointRepositoryMother::inMemory();
        $serviceCategoryId = ServiceCategoryId::generate();
        $otherServiceCategoryId = ServiceCategoryId::generate();

        $cp1 = CriticalPointMother::withServiceCategories($serviceCategoryId);
        $cp2 = CriticalPointMother::withServiceCategories($serviceCategoryId, $otherServiceCategoryId);
        $cp3 = CriticalPointMother::withServiceCategories($otherServiceCategoryId);

        $repository->add($cp1);
        $repository->add($cp2);
        $repository->add($cp3);

        $sut = new UnlinkServiceCategorySubscriber($repository);
        $sut->handle(new ServiceCategoryRemoved($serviceCategoryId));

        $updatedCp1 = $repository->find($cp1->id());
        $updatedCp2 = $repository->find($cp2->id());
        $updatedCp3 = $repository->find($cp3->id());

        $this->assertNotNull($updatedCp1);
        $this->assertNotNull($updatedCp2);
        $this->assertNotNull($updatedCp3);

        $this->assertEmpty($updatedCp1->serviceCategoryIds());
        $this->assertCount(1, $updatedCp2->serviceCategoryIds());
        $this->assertNotEmpty($updatedCp3->serviceCategoryIds());
    }

    public function testHandleDoesNothingIfNoLinkedCriticalPoints(): void
    {
        $repository = CriticalPointRepositoryMother::inMemory();

        $cp = CriticalPointMother::random();
        $repository->add($cp);

        $sut = new UnlinkServiceCategorySubscriber($repository);
        $sut->handle(new ServiceCategoryRemoved(ServiceCategoryId::generate()));

        $this->assertNotNull($repository->find($cp->id()));
    }
}
