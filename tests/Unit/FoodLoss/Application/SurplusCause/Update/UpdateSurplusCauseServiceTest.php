<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\SurplusCause\Update;

use App\FoodLoss\Application\SurplusCause\SurplusCauseAuthorizer;
use App\FoodLoss\Application\SurplusCause\Update\UpdateSurplusCauseRequest;
use App\FoodLoss\Application\SurplusCause\Update\UpdateSurplusCauseService;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseUpdated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseMother;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class UpdateSurplusCauseServiceTest extends TestCase
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

        $surplusCause = SurplusCauseMother::fromName('Original name');
        $repository->add($surplusCause);

        $sut = $this->createSUT($repository);

        $request = new UpdateSurplusCauseRequest(
            surplusCauseId: $surplusCause->id(),
            name: 'Updated name',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertEquals('Updated name', $result->name);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof SurplusCauseUpdated);
        $this->assertEquals('Updated name', $lastEvent->name());
    }

    public function testIsHandle(): void
    {
        $repository = SurplusCauseRepositoryMother::inMemory();
        $surplusCause = SurplusCauseMother::registeredSurplusCause();

        $sut = $this->createSUT($repository);

        $result = $sut->isHandled(new UpdateSurplusCauseRequest(
            surplusCauseId: $surplusCause->id(),
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(SurplusCauseRepository $repository, Locale $locale = Locale::Spanish): UpdateSurplusCauseService
    {
        FakeLocaleSetter::set($locale);

        return new UpdateSurplusCauseService(
            surplusCauseRepository: $repository,
            authorizer: new SurplusCauseAuthorizer(),
        );
    }
}
