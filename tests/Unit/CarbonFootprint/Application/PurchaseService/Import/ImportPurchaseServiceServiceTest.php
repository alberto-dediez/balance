<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PurchaseService\Import;

use App\CarbonFootprint\Application\PurchaseService\Import\ImportPurchaseServiceRequest;
use App\CarbonFootprint\Application\PurchaseService\Import\ImportPurchaseServiceService;
use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use PHPUnit\Framework\TestCase;

class ImportPurchaseServiceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        FakeLocaleSetter::reset();
        FakeLocaleSetter::set(Locale::Spanish);
    }

    public function testCreatesNewPurchaseServiceByNameWithDefaults(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $request = new ImportPurchaseServiceRequest(
            name: 'Consultoría',
            year: '2024',
            co2eq: '1.5',
            co2: '1.0',
            ch4: '0.3',
            n2o: '0.2',
            source: 'IPCC',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $purchaseService = $repository->find($result->purchaseServiceId);

        $this->assertNotNull($purchaseService);
        $this->assertEquals('Consultoría', $purchaseService->name());
        $this->assertEquals(PurchaseServiceCategory::Others, $purchaseService->category());
        $this->assertEquals(Unit::Euro, $purchaseService->unit());

        $emissionFactor = $purchaseService->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId());

        $this->assertNotNull($emissionFactor);
        $this->assertEquals('1.5', $emissionFactor->emissionFactor()->co2eq());
        $this->assertEquals(Unit::Euro, $emissionFactor->emissionFactor()->unit());
    }

    public function testCreatesNewPurchaseServiceWithMappedCategoryAndUnit(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory(Locale::Spanish);

        $sut = $this->createSUT($repository);

        $result = $sut(
            new ImportPurchaseServiceRequest(
                name: 'Publicidad',
                category: PurchaseServiceCategory::Marketing->value,
                unit: Unit::UsDollar->value,
                year: '2024',
                co2eq: '1.5',
                co2: '1.0',
                ch4: '0.3',
                n2o: '0.2',
                emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
            ),
            UserMother::adminUser(),
        );

        $purchaseService = $repository->find($result->purchaseServiceId);

        $this->assertNotNull($purchaseService);
        $this->assertEquals(PurchaseServiceCategory::Marketing, $purchaseService->category());
        $this->assertEquals(Unit::UsDollar, $purchaseService->unit());
    }

    public function testExplicitPurchaseServiceOnlyUpdatesEmissionFactor(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory(Locale::Spanish);
        $existing = PurchaseServiceMother::from(name: 'Consultoría', id: (string) PurchaseServiceId::generate(), category: PurchaseServiceCategory::ProfessionalServices);
        $repository->add($existing);

        $sut = $this->createSUT($repository);

        $request = new ImportPurchaseServiceRequest(
            purchaseService: (string) $existing->id(),
            name: 'Otro nombre',
            category: PurchaseServiceCategory::Technology->value,
            year: '2025',
            co2eq: '3.0',
            emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId(),
        );

        $result = $sut($request, UserMother::adminUser());

        $purchaseService = $repository->find($result->purchaseServiceId);

        $this->assertNotNull($purchaseService);
        $this->assertEquals('Consultoría', $purchaseService->name());
        $this->assertEquals(PurchaseServiceCategory::ProfessionalServices, $purchaseService->category());
        $this->assertEquals('3.0', $purchaseService->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId())?->emissionFactor()->co2eq());
    }

    public function testThrowsWhenNeitherPurchaseServiceNorNameProvided(): void
    {
        $sut = $this->createSUT(PurchaseServiceRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportPurchaseServiceRequest(year: '2024', emissionFactorGroup: (string) EmissionFactorGroupMother::defaultId()), UserMother::adminUser());
    }

    public function testThrowsWhenEmissionFactorGroupIsMissing(): void
    {
        $sut = $this->createSUT(PurchaseServiceRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportPurchaseServiceRequest(name: 'Consultoría', year: '2024', co2eq: '1.5'), UserMother::adminUser());
    }

    public function testThrowsWhenYearIsInvalid(): void
    {
        $sut = $this->createSUT(PurchaseServiceRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(new ImportPurchaseServiceRequest(name: 'Consultoría'), UserMother::adminUser());
    }

    public function testThrowsWhenExplicitPurchaseServiceNotFound(): void
    {
        $sut = $this->createSUT(PurchaseServiceRepositoryMother::inMemory(Locale::Spanish));

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new ImportPurchaseServiceRequest(
                purchaseService: (string) PurchaseServiceId::generate(),
                year: '2024',
            ),
            UserMother::adminUser(),
        );
    }

    public function testIsHandled(): void
    {
        $sut = $this->createSUT(PurchaseServiceRepositoryMother::inMemory(Locale::Spanish));

        $this->assertTrue($sut->isHandled(new ImportPurchaseServiceRequest(name: 'Consultoría', year: '2024')));
    }

    private function createSUT(PurchaseServiceRepository $repository): ImportPurchaseServiceService
    {
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add(EmissionFactorGroupMother::default());

        return new ImportPurchaseServiceService(
            purchaseServiceRepository: $repository,
            authorizer: new PurchaseServiceAuthorizer(),
            emissionFactorGroupRepository: $emissionFactorGroupRepository,
        );
    }
}
