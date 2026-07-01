<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\Update;

use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\EmissionFactorRow;
use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\Update\UpdateEmissionFactorsRequest;
use App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\Update\UpdateEmissionFactorsService;
use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Security\Domain\Model\User\Role;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepositoryMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepositoryMother;
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
        $repository = MerchandisingItemRepositoryMother::inMemory();

        $merchandisingItem = MerchandisingItemMother::random();
        $repository->add($merchandisingItem);

        $emissionFactorGroup = EmissionFactorGroupMother::default();
        $emissionFactorGroupRepository = EmissionFactorGroupRepositoryMother::inMemory();
        $emissionFactorGroupRepository->add($emissionFactorGroup);

        $sut = $this->createSUT($repository, $emissionFactorGroupRepository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    merchandisingItemId: (string) $merchandisingItem->id(),
                    merchandisingItemName: $merchandisingItem->name(),
                    co2eq: '1.5',
                    co2: '1.0',
                    ch4: '0.2',
                    n2o: '0.3',
                    source: 'IPCC',
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($merchandisingItem->id());

        $this->assertNotNull($persisted);
        $factor = $persisted->emissionFactorFor(2025, $emissionFactorGroup->id());
        $this->assertNotNull($factor);
        $this->assertSame($emissionFactorGroup->co2eqFor('1.0', '0.2', '0.3'), $factor->newFactor()->co2eq());
    }

    public function testRemovesEmissionFactorWhenRowIsEmpty(): void
    {
        $repository = MerchandisingItemRepositoryMother::inMemory();

        $emissionFactorGroup = EmissionFactorGroupMother::default();

        $merchandisingItem = MerchandisingItemMother::random();
        $ef = new EmissionFactor('1', '1', '1', '1', 2025, emissionFactorGroupId: $emissionFactorGroup->id());
        $merchandisingItem->setEmissionFactor($ef, $ef);
        $repository->add($merchandisingItem);

        $sut = $this->createSUT($repository);

        $request = new UpdateEmissionFactorsRequest(
            year: 2025,
            rows: [
                new EmissionFactorRow(
                    merchandisingItemId: (string) $merchandisingItem->id(),
                    merchandisingItemName: $merchandisingItem->name(),
                    emissionFactorGroupId: (string) $emissionFactorGroup->id(),
                ),
            ],
        );

        $sut($request, UserMother::fromUsernameAndRole('admin', Role::Admin));

        $persisted = $repository->find($merchandisingItem->id());

        $this->assertNotNull($persisted);
        $this->assertNull($persisted->emissionFactorFor(2025, $emissionFactorGroup->id()));
    }

    private function createSUT(
        MerchandisingItemRepository $repository,
        ?EmissionFactorGroupRepository $emissionFactorGroupRepository = null,
    ): UpdateEmissionFactorsService {
        return new UpdateEmissionFactorsService(
            $repository,
            new MerchandisingItemAuthorizer(),
            $emissionFactorGroupRepository ?? EmissionFactorGroupRepositoryMother::inMemory(),
        );
    }
}
