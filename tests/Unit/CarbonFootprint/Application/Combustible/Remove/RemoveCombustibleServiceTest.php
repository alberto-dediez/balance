<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\Combustible\Remove;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Application\Combustible\Remove\RemoveCombustibleRequest;
use App\CarbonFootprint\Application\Combustible\Remove\RemoveCombustibleService;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRemoved;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\Combustible\CombustibleRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RemoveCombustibleServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testInvoke(): void
    {
        $repository = CombustibleRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $combustible = CombustibleMother::registeredCombustible();

        $repository->add($combustible);

        $request = new RemoveCombustibleRequest(
            combustibleId: $combustible->id(),
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($combustible->id()->equals($result->combustibleId));

        $combustiblePersisted = $repository->find($result->combustibleId);

        $this->assertNull($combustiblePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CombustibleRemoved);
    }

    public function testIsHandle(): void
    {
        $repository = CombustibleRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $combustible = CombustibleMother::random();

        $result = $sut->isHandled(new RemoveCombustibleRequest(
            combustibleId: $combustible->id(),
        ));

        $this->assertTrue($result);
    }

    private function createSUT(CombustibleRepository $repository): RemoveCombustibleService
    {
        return new RemoveCombustibleService($repository, new CombustibleAuthorizer());
    }
}
