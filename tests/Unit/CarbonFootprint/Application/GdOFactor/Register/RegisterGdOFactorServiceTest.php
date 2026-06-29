<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\GdOFactor\Register;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Application\GdOFactor\Register\RegisterGdOFactorRequest;
use App\CarbonFootprint\Application\GdOFactor\Register\RegisterGdOFactorService;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRegistered;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use PHPUnit\Framework\TestCase;

class RegisterGdOFactorServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $request = new RegisterGdOFactorRequest(
            name: 'GdO Factor 1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->gdOFactorId);
        $this->assertEquals(36, strlen((string) $result->gdOFactorId));
        $this->assertEquals('GdO Factor 1', $result->name);

        $gdOFactorPersisted = $repository->find($result->gdOFactorId);

        $this->assertNotNull($gdOFactorPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof GdOFactorRegistered);
        $this->assertEquals('GdO Factor 1', $lastEvent->name());
        $this->assertTrue($gdOFactorPersisted->id()->equals($lastEvent->gdOFactorId()));
    }

    public function testIsHandle(): void
    {
        $repository = GdOFactorRepositoryMother::inMemory();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RegisterGdOFactorRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(GdOFactorRepository $repository): RegisterGdOFactorService
    {
        return new RegisterGdOFactorService(
            gdOFactorRepository: $repository,
            authorizer: new GdOFactorAuthorizer(),
        );
    }
}
