<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\GdOFactor\Remove;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Application\GdOFactor\Remove\RemoveGdOFactorRequest;
use App\CarbonFootprint\Application\GdOFactor\Remove\RemoveGdOFactorService;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRemoved;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveGdOFactorServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $gdOFactor = GdOFactorMother::registeredGdOFactor();

        $repository->add($gdOFactor);

        $request = new RemoveGdOFactorRequest(
            gdOFactorId: $gdOFactor->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($gdOFactor->id()->equals($result->gdOFactorId));

        $gdOFactorPersisted = $repository->find($result->gdOFactorId);

        $this->assertNull($gdOFactorPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof GdOFactorRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $gdOFactor = GdOFactorMother::random();

        $result = $sut->isHandled(new RemoveGdOFactorRequest(
            gdOFactorId: $gdOFactor->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(GdOFactorRepository $repository): RemoveGdOFactorService
    {
        return new RemoveGdOFactorService($repository, new GdOFactorAuthorizer());
    }
}
