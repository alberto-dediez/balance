<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CateringItem\EmissionFactor\Update;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\CateringItem\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepositoryMother;
use App\Tests\Unit\Security\Domain\Model\User\UserMother;
use PHPUnit\Framework\TestCase;

class UpdateEmissionFactorsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
    }

    public function testAddsEmissionFactorForYear(): void
    {
        $repository = CateringItemRepositoryMother::inMemory();

        $cateringItem = CateringItemMother::random();
        $repository->add($cateringItem);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    cateringItemId: (string) $cateringItem->id(),
                    cateringItemName: $cateringItem->name(),
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($cateringItem->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025);
        $this->assertNotNull($factor);
        $this->assertSame('1.5', $factor->co2eq());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = CateringItemRepositoryMother::inMemory();

        $cateringItem = CateringItemMother::random();
        $cateringItem->setEmissionFactor(new EmissionFactor('1', '1', '1', '1', 2025));
        $repository->add($cateringItem);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    cateringItemId: (string) $cateringItem->id(),
                    cateringItemName: $cateringItem->name(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($cateringItem->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025));
    }

    private function createSUT(CateringItemRepository $repository): UpdateEmissionFactorsService
    {
        return new UpdateEmissionFactorsService($repository, new CateringItemAuthorizer());
    }
}
