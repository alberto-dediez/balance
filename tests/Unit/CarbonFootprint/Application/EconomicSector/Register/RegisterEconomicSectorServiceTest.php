<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EconomicSector\Register;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Application\EconomicSector\Register\RegisterEconomicSectorRequest;
use App\CarbonFootprint\Application\EconomicSector\Register\RegisterEconomicSectorService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorCodeAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorFilter;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRegistered;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorTranslated;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterEconomicSectorServiceTest extends TestCase
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
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterEconomicSectorRequest(
            name: 'Fabricación de productos metálicos',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->economicSectorId);
        $this->assertEquals(36, strlen((string) $result->economicSectorId));
        $this->assertEquals('Fabricación de productos metálicos', $result->name);

        $economicSectorPersisted = $repository->find($result->economicSectorId);

        $this->assertNotNull($economicSectorPersisted);
        $this->assertSame(Unit::Euro, $economicSectorPersisted->unit());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EconomicSectorRegistered);
        $this->assertEquals('Fabricación de productos metálicos', $lastEvent->name());
        $this->assertSame(Unit::Euro, $lastEvent->unit());
        $this->assertTrue($economicSectorPersisted->id()->equals($lastEvent->economicSectorId()));
    }

    public function testRegisterWithNormalizedClassificationCode(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut(new RegisterEconomicSectorRequest(name: 'Programación informática', classificationCode: ' 62.01a '), UserMother::adminUser());

        $this->assertSame('62.01A', $repository->find($result->economicSectorId)?->classificationCode());
    }

    public function testRegisterWithDuplicatedClassificationCodeThrows(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);
        $repository->add(EconomicSectorMother::withCode('25'));

        $sut = $this->createSUT($repository, Locale::Spanish);

        try {
            $sut(new RegisterEconomicSectorRequest(name: 'Otro sector', classificationCode: '25'), UserMother::adminUser());
            $this->fail('EconomicSectorCodeAlreadyExistsException expected');
        } catch (EconomicSectorCodeAlreadyExistsException $exception) {
            $this->assertSame('25', $exception->code());
        }

        $this->assertSame(1, $repository->totalResultsByFilter(new EconomicSectorFilter()));
    }

    public function testRegisterReusesClassificationCodeOfRemovedSector(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);
        $removed = EconomicSectorMother::withCode('25');
        $removed->remove();
        $repository->add($removed);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut(new RegisterEconomicSectorRequest(name: 'Fabricación de productos metálicos', classificationCode: '25'), UserMother::adminUser());

        $this->assertSame('25', $repository->find($result->economicSectorId)?->classificationCode());
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterEconomicSectorRequest(
            name: 'changedName',
            unit: Unit::Euro,
        );

        $result = $sut($request, UserMother::adminUser());

        $economicSectorPersisted = $repository->find($result->economicSectorId);

        $this->assertNotNull($economicSectorPersisted);
        $this->assertEquals('changedName', $economicSectorPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof EconomicSectorTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($economicSectorPersisted->id()->equals($lastEvent->economicSectorId()));
    }

    public function testOwnerCannotRegister(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $this->expectException(UnauthorizedActionException::class);

        $sut(new RegisterEconomicSectorRequest(name: 'test'), UserMother::fromUsernameAndRole('owner', Role::Owner));
    }

    public function testIsHandle(): void
    {
        $repository = EconomicSectorRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterEconomicSectorRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(EconomicSectorRepository $repository, Locale $locale): RegisterEconomicSectorService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterEconomicSectorService(
            economicSectorRepository: $repository,
            authorizer: new EconomicSectorAuthorizer(),
        );
    }
}
