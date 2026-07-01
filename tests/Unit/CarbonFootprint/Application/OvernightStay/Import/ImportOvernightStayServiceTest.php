<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\OvernightStay\Import;

use App\CarbonFootprint\Application\OvernightStay\Import\ImportOvernightStayRequest;
use App\CarbonFootprint\Application\OvernightStay\Import\ImportOvernightStayService;
use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayFilter;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayId;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportOvernightStayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewOvernightStayByNameWhenNoOvernightStayIsSelected(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportOvernightStayRequest(
            name: 'Pernoctación 1',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
        );

        $result = $sut($request, UserMother::adminUser());

        $overnightStay = $repository->find($result->overnightStayId);

        $this->assertNotNull($overnightStay);
        $this->assertEquals('Pernoctación 1', $overnightStay->name());

        $emissionFactor = $overnightStay->emissionFactorFor(2024, EmissionFactorGroupId::default());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->co2eq());
        $this->assertEquals(Unit::Pernoctacion, $emissionFactor->unit());
    }

    public function testUpsertsExistingOvernightStayByName(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);
        $existing = OvernightStayMother::fromName('Pernoctación 1');
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportOvernightStayRequest(
            name: 'Pernoctación 1',
            year: '2024',
            co2eq: '2.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $this->assertTrue($existing->id()->equals($result->overnightStayId));

        $overnightStay = $repository->find($result->overnightStayId);

        $this->assertNotNull($overnightStay);
        $this->assertCount(1, $repository->findByFilter(new OvernightStayFilter(limit: 0)));
        $this->assertEquals('2.0', $overnightStay->emissionFactorFor(2024, EmissionFactorGroupId::default())?->co2eq());
    }

    public function testExplicitOvernightStayOnlyUpdatesEmissionFactor(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);
        $existing = OvernightStayMother::fromNameAndId('Pernoctación 1', (string) OvernightStayId::generate());
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportOvernightStayRequest(
            overnightStay: (string) $existing->id(),
            name: 'Otro nombre',
            year: '2025',
            co2eq: '3.0',
        );

        $result = $sut($request, UserMother::adminUser());

        $overnightStay = $repository->find($result->overnightStayId);

        $this->assertNotNull($overnightStay);
        $this->assertEquals('Pernoctación 1', $overnightStay->name());
        $this->assertEquals('3.0', $overnightStay->emissionFactorFor(2025, EmissionFactorGroupId::default())?->co2eq());
    }

    public function testThrowsWhenNeitherOvernightStayNorNameProvided(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportOvernightStayRequest(year: '2024'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportOvernightStayRequest(name: 'Pernoctación 1'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitOvernightStayNotFound(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportOvernightStayRequest(
                overnightStay: (string) OvernightStayId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $repository = OvernightStayRepositoryMother::inMemory(Locale::Spanish);
        $sut = $this->createSUT($repository);

        $this->assertTrue($sut->isHandled(new ImportOvernightStayRequest(name: 'Pernoctación 1', year: '2024')));
    }

    private function createSUT(OvernightStayRepository $repository): ImportOvernightStayService
    {
        return new ImportOvernightStayService(
            overnightStayRepository: $repository,
            authorizer: new OvernightStayAuthorizer(),
        );
    }
}
