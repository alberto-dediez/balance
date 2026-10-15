<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\PurchaseService;

use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceFilter;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class PurchaseServiceRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddPurchaseServiceToRepository(): void
    {
        $repository = $this->createSUT();

        $purchaseService = PurchaseServiceMother::registeredPurchaseService();

        $this->assertNull($repository->find($purchaseService->id()));

        $repository->add($purchaseService);

        $recoveredPurchaseService = $repository->find($purchaseService->id());

        $this->assertNotNull($recoveredPurchaseService);
        $this->assertEquals($purchaseService->id(), $recoveredPurchaseService->id());
    }

    public function testRemovePurchaseServiceToRepository(): void
    {
        $repository = $this->createSUT();

        $purchaseService = PurchaseServiceMother::registeredPurchaseService();

        $repository->add($purchaseService);

        $recoveredPurchaseService = $repository->find($purchaseService->id());

        $this->assertNotNull($recoveredPurchaseService);
        $this->assertEquals($purchaseService->id(), $recoveredPurchaseService->id());

        $repository->remove($purchaseService);

        $this->assertNull($repository->find($purchaseService->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::fromName('Nombre específico'));

        $filter = new PurchaseServiceFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new PurchaseServiceFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::random());
        $repository->add(PurchaseServiceMother::fromName('Nombre específico'));

        $filter = new PurchaseServiceFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $purchaseService = PurchaseServiceMother::registeredPurchaseService();

        $repository->add($purchaseService);

        $recoveredPurchaseService = $repository->find($purchaseService->id());

        $this->assertNotNull($recoveredPurchaseService);

        $recoveredPurchaseService->remove();

        $repository->add($purchaseService);

        $this->assertNull($repository->find($purchaseService->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $purchaseService = PurchaseServiceMother::registeredPurchaseService();

        $this->assertNull($repository->find($purchaseService->id()));

        $purchaseService->translate('translated-name', Locale::English);

        $repository->add($purchaseService);

        $recoveredPurchaseService = $repository->find($purchaseService->id());

        $this->assertNotNull($recoveredPurchaseService);
        $this->assertEquals($purchaseService->id(), $recoveredPurchaseService->id());
        $this->assertEquals('translated-name', $recoveredPurchaseService->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $purchaseService = PurchaseServiceMother::registeredPurchaseService();

        $purchaseService->translate('translated-name', Locale::English);

        $repository->add($purchaseService);

        $recoveredPurchaseServices = $repository->findByFilter(new PurchaseServiceFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredPurchaseServices);
        $this->assertEquals($purchaseService->id(), $recoveredPurchaseServices[0]->id());
        $this->assertEquals('translated-name', $recoveredPurchaseServices[0]->name());
    }

    public function testFindByFilterWithCategory(): void
    {
        $repository = $this->createSUT();

        $repository->add(PurchaseServiceMother::from('Consultoría', (string) PurchaseServiceId::generate(), PurchaseServiceCategory::ProfessionalServices));
        $repository->add(PurchaseServiceMother::from('Publicidad', (string) PurchaseServiceId::generate(), PurchaseServiceCategory::Marketing));
        $repository->add(PurchaseServiceMother::from('Campaña', (string) PurchaseServiceId::generate(), PurchaseServiceCategory::Marketing));

        $filter = new PurchaseServiceFilter(category: PurchaseServiceCategory::Marketing);

        $this->assertCount(2, $repository->findByFilter($filter));
        $this->assertSame(2, $repository->totalResultsByFilter($filter));
    }

    public function testFindByFilterOrdersByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(PurchaseServiceMother::fromName('B'));
        $repository->add(PurchaseServiceMother::fromName('C'));
        $repository->add(PurchaseServiceMother::fromName('A'));

        $filter = new PurchaseServiceFilter();

        $this->assertSame(['A', 'B', 'C'], $this->names($repository->findByFilter($filter)));

        $filter->orderWay = 'DESC';

        $this->assertSame(['C', 'B', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByRegisteredAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->purchaseService('A', registeredAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->purchaseService('B', registeredAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->purchaseService('C', registeredAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new PurchaseServiceFilter();
        $filter->orderBy = 'registeredAt';

        $this->assertSame(['B', 'C', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByUpdatedAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->purchaseService('A', updatedAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->purchaseService('B', updatedAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->purchaseService('C', updatedAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new PurchaseServiceFilter();
        $filter->orderBy = 'updatedAt';
        $filter->orderWay = 'DESC';

        $this->assertSame(['A', 'C', 'B'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterPaginatesAndCountsTotal(): void
    {
        $repository = $this->createSUT();

        foreach (['A', 'B', 'C', 'D', 'E'] as $name) {
            $repository->add(PurchaseServiceMother::fromName($name));
        }

        $filter = new PurchaseServiceFilter(limit: 2);
        $filter->page = 1;

        $this->assertSame(['C', 'D'], $this->names($repository->findByFilter($filter)));
        $this->assertSame(5, $repository->totalResultsByFilter($filter));
    }

    private function purchaseService(
        string $name,
        \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        ?\DateTimeImmutable $updatedAt = null,
    ): PurchaseService {
        return new PurchaseService(
            id: PurchaseServiceId::generate(),
            name: $name,
            category: PurchaseServiceCategory::Others,
            unit: Unit::Euro,
            registeredAt: $registeredAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param array<PurchaseService> $purchaseServices
     *
     * @return list<string>
     */
    private function names(array $purchaseServices): array
    {
        return array_values(array_map(static fn(PurchaseService $purchaseService): string => $purchaseService->name(), $purchaseServices));
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): PurchaseServiceRepository;
}
