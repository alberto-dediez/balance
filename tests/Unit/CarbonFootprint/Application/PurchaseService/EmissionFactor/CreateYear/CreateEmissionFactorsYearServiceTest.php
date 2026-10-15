<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\PurchaseService\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\PurchaseService\EmissionFactor\CreateYear\CreateEmissionFactorsYearRequest;
use App\CarbonFootprint\Application\PurchaseService\EmissionFactor\CreateYear\CreateEmissionFactorsYearService;
use App\CarbonFootprint\Application\PurchaseService\EmissionFactor\CreateYear\EmissionFactorYearAlreadyExistsException;
use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class CreateEmissionFactorsYearServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testCopiesFactorsFromSourceYearToTargetYear(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $purchaseService = PurchaseServiceMother::random();
        $ef = new EmissionFactor('1.5', '1.0', '0.2', '0.3', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId(), source: 'IPCC');
        $purchaseService->setEmissionFactor($ef);
        $repository->add($purchaseService);

        $sut = $this->createSUT($repository);

        $request = new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025);

        $response = $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $this->assertSame(2025, $response->targetYear);

        $persisted = $repository->find($purchaseService->id());
        $this->assertNotNull($persisted);

        $copied = $persisted->emissionFactorFor(2025, EmissionFactorGroupMother::defaultId());
        $this->assertNotNull($copied);
        $this->assertSame('1.5', $copied->emissionFactor()->co2eq());
        $this->assertSame('IPCC', $copied->emissionFactor()->source());

        $this->assertNotNull($persisted->emissionFactorFor(2024, EmissionFactorGroupMother::defaultId()));
    }

    public function testThrowsWhenTargetYearAlreadyExists(): void
    {
        $repository = PurchaseServiceRepositoryMother::inMemory();

        $purchaseService = PurchaseServiceMother::random();
        $ef2024 = new EmissionFactor('1', '1', '1', '1', 2024, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $purchaseService->setEmissionFactor($ef2024);
        $ef2025 = new EmissionFactor('2', '2', '2', '2', 2025, emissionFactorGroupId: EmissionFactorGroupMother::defaultId());
        $purchaseService->setEmissionFactor($ef2025);
        $repository->add($purchaseService);

        $sut = $this->createSUT($repository);

        $this->expectException(EmissionFactorYearAlreadyExistsException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2025),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    public function testThrowsWhenTargetYearEqualsSourceYear(): void
    {
        $sut = $this->createSUT(PurchaseServiceRepositoryMother::inMemory());

        $this->expectException(\InvalidArgumentException::class);

        $sut(
            new CreateEmissionFactorsYearRequest(sourceYear: 2024, emissionFactorGroupId: (string) EmissionFactorGroupMother::defaultId(), targetYear: 2024),
            UserMother::fromUsernameAndRole('admin', Role::Admin),
        );
    }

    private function createSUT(PurchaseServiceRepository $repository): CreateEmissionFactorsYearService
    {
        return new CreateEmissionFactorsYearService($repository, new PurchaseServiceAuthorizer());
    }
}
