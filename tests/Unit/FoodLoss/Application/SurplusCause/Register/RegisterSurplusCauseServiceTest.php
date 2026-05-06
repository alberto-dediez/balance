<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Application\SurplusCause\Register;

use App\FoodLoss\Application\SurplusCause\Register\RegisterSurplusCauseRequest;
use App\FoodLoss\Application\SurplusCause\Register\RegisterSurplusCauseService;
use App\FoodLoss\Application\SurplusCause\SurplusCauseAuthorizer;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRegistered;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseTranslated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterSurplusCauseServiceTest extends TestCase
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

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterSurplusCauseRequest(
            name: 'Surplus cause 1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->surplusCauseId);
        $this->assertEquals(36, strlen((string) $result->surplusCauseId));
        $this->assertEquals('Surplus cause 1', $result->name);

        $surplusCausePersisted = $repository->find($result->surplusCauseId);

        $this->assertNotNull($surplusCausePersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof SurplusCauseRegistered);
        $this->assertEquals('Surplus cause 1', $lastEvent->name());
        $this->assertTrue($surplusCausePersisted->id()->equals($lastEvent->surplusCauseId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = SurplusCauseRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterSurplusCauseRequest(
            name: 'changedName',
        );

        $result = $sut($request, UserMother::adminUser());

        $surplusCausePersisted = $repository->find($result->surplusCauseId);

        $this->assertNotNull($surplusCausePersisted);
        $this->assertEquals('changedName', $surplusCausePersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof SurplusCauseTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($surplusCausePersisted->id()->equals($lastEvent->surplusCauseId()));
    }

    public function testIsHandle(): void
    {
        $repository = SurplusCauseRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterSurplusCauseRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(SurplusCauseRepository $repository, Locale $locale): RegisterSurplusCauseService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterSurplusCauseService(
            surplusCauseRepository: $repository,
            authorizer: new SurplusCauseAuthorizer(),
        );
    }
}
