<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\SurplusCause\Remove;

use App\FoodLoss\Application\SurplusCause\Remove\RemoveSurplusCauseRequest;
use App\FoodLoss\Application\SurplusCause\Remove\RemoveSurplusCauseService;
use App\FoodLoss\Application\SurplusCause\SurplusCauseAuthorizer;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRemoved;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseMother;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RemoveSurplusCauseServiceTest extends TestCase
{
    private SpyDomainEventSubscriber $spySubscriber;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();

        $this->spySubscriber = new SpyDomainEventSubscriber();
    }

    public function testUseCase(): void
    {
        $repository = SurplusCauseRepositoryMother::inMemory();

        $surplusCause = SurplusCauseMother::registeredSurplusCause();
        $repository->add($surplusCause);

        $sut = $this->createSUT($repository);

        $result = $sut(new RemoveSurplusCauseRequest($surplusCause->id()), UserMother::adminUser());

        $this->assertTrue($surplusCause->id()->equals($result->surplusCauseId));

        $this->assertNull($repository->find($surplusCause->id()));

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof SurplusCauseRemoved);
        $this->assertTrue($surplusCause->id()->equals($lastEvent->surplusCauseId()));
    }

    public function testIsHandle(): void
    {
        $repository = SurplusCauseRepositoryMother::inMemory();
        $surplusCause = SurplusCauseMother::registeredSurplusCause();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new RemoveSurplusCauseRequest($surplusCause->id()));

        $this->assertTrue($result);
    }

    private function createSUT(SurplusCauseRepository $repository): RemoveSurplusCauseService
    {
        return new RemoveSurplusCauseService(
            repository: $repository,
            authorizer: new SurplusCauseAuthorizer(),
        );
    }
}
