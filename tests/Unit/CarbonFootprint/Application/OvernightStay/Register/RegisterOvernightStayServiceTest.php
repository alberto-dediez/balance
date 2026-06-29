<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OvernightStay\Register;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Application\OvernightStay\Register\RegisterOvernightStayRequest;
use App\CarbonFootprint\Application\OvernightStay\Register\RegisterOvernightStayService;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRegistered;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayTranslated;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Domain\Event\SpyDomainEventSubscriber;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class RegisterOvernightStayServiceTest extends TestCase
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
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $request = new RegisterOvernightStayRequest(
            name: 'Pernoctación 1',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertNotEmpty((string) $result->overnightStayId);
        $this->assertEquals(36, strlen((string) $result->overnightStayId));
        $this->assertEquals('Pernoctación 1', $result->name);

        $overnightStayPersisted = $repository->find($result->overnightStayId);

        $this->assertNotNull($overnightStayPersisted);

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof OvernightStayRegistered);
        $this->assertEquals('Pernoctación 1', $lastEvent->name());
        $this->assertTrue($overnightStayPersisted->id()->equals($lastEvent->overnightStayId()));
    }

    public function testRegisterWithLocaleTranslate(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::English);

        $request = new RegisterOvernightStayRequest(
            name: 'changedName',
        );

        $result = $sut($request, UserMother::adminUser());

        $overnightStayPersisted = $repository->find($result->overnightStayId);

        $this->assertNotNull($overnightStayPersisted);
        $this->assertEquals('changedName', $overnightStayPersisted->name());

        $lastEvent = $this->spySubscriber->lastEvent();

        $this->assertNotNull($lastEvent);
        $this->assertTrue($lastEvent instanceof OvernightStayTranslated);
        $this->assertEquals('changedName', $lastEvent->name());
        $this->assertEquals(Locale::English, $lastEvent->locale());
        $this->assertTrue($overnightStayPersisted->id()->equals($lastEvent->overnightStayId()));
    }

    public function testIsHandle(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository, Locale::Spanish);

        $result = $sut->isHandled(new RegisterOvernightStayRequest(
            name: 'test',
        ));

        $this->assertTrue($result);
    }

    private function createSUT(OvernightStayRepository $repository, Locale $locale): RegisterOvernightStayService
    {
        FakeLocaleSetter::set($locale);

        return new RegisterOvernightStayService(
            overnightStayRepository: $repository,
            authorizer: new OvernightStayAuthorizer(),
        );
    }
}
