<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CapitalGood\Register;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Application\CapitalGood\Register\RegisterCapitalGoodRequest;
use App\CarbonFootprint\Application\CapitalGood\Register\RegisterCapitalGoodService;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRegistered;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodTranslated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterCapitalGoodServiceTest extends TestCase
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
        $repository = CapitalGoodRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterCapitalGoodRequest(
            name: 'Reforma de oficinas',
            category: CapitalGoodCategory::VehiclesAndMachinery,
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->capitalGoodId);
        $this->assertEquals(36, strlen((string) $result->capitalGoodId));
        $this->assertEquals('Reforma de oficinas', $result->name);

        $capitalGoodPersisted = $repository->find($result->capitalGoodId);

        $this->assertNotNull($capitalGoodPersisted);
        $this->assertSame(Unit::Euro, $capitalGoodPersisted->unit());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CapitalGoodRegistered);
        $this->assertEquals('Reforma de oficinas', $lastEvent->name());
        $this->assertSame(Unit::Euro, $lastEvent->unit());
        $this->assertTrue($capitalGoodPersisted->id()->equals($lastEvent->capitalGoodId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterCapitalGoodRequest(
            name: 'changedName',
            category: CapitalGoodCategory::VehiclesAndMachinery,
            unit: Unit::Piece,
        );

        $result = $sut($request, UserMother::adminUser());

        $capitalGoodPersisted = $repository->find($result->capitalGoodId);

        $this->assertNotNull($capitalGoodPersisted);
        $this->assertEquals('changedName', $capitalGoodPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof CapitalGoodTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($capitalGoodPersisted->id()->equals($lastEvent->capitalGoodId()));
    }

    public function testOwnerCannotRegister(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RegisterCapitalGoodRequest(name: 'test'), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = CapitalGoodRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterCapitalGoodRequest(
            name: 'test',
            category: CapitalGoodCategory::Renovations,
        ));

        $this->assertTrue($result);
    }

    private function createSUT(CapitalGoodRepository $repository, Locale $locale): RegisterCapitalGoodService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterCapitalGoodService(
            capitalGoodRepository: $repository,
            authorizer: new CapitalGoodAuthorizer(),
        );
    }
}
