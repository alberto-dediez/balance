<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\UseProfile;

use App\CarbonFootprint\Domain\Model\Unit;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileFilter;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class UseProfileRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddUseProfileToRepository(): void
    {
        $repository = $this->createSUT();

        $useProfile = UseProfileMother::registeredUseProfile();

        $this->assertNull($repository->find($useProfile->id()));

        $repository->add($useProfile);

        $recoveredUseProfile = $repository->find($useProfile->id());

        $this->assertNotNull($recoveredUseProfile);
        $this->assertEquals($useProfile->id(), $recoveredUseProfile->id());
    }

    public function testRemoveUseProfileToRepository(): void
    {
        $repository = $this->createSUT();

        $useProfile = UseProfileMother::registeredUseProfile();

        $repository->add($useProfile);

        $recoveredUseProfile = $repository->find($useProfile->id());

        $this->assertNotNull($recoveredUseProfile);
        $this->assertEquals($useProfile->id(), $recoveredUseProfile->id());

        $repository->remove($useProfile);

        $this->assertNull($repository->find($useProfile->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::fromName('Nombre específico'));

        $filter = new UseProfileFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new UseProfileFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::random());
        $repository->add(UseProfileMother::fromName('Nombre específico'));

        $filter = new UseProfileFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $useProfile = UseProfileMother::registeredUseProfile();

        $repository->add($useProfile);

        $recoveredUseProfile = $repository->find($useProfile->id());

        $this->assertNotNull($recoveredUseProfile);

        $recoveredUseProfile->remove();

        $repository->add($useProfile);

        $this->assertNull($repository->find($useProfile->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $useProfile = UseProfileMother::registeredUseProfile();

        $this->assertNull($repository->find($useProfile->id()));

        $useProfile->translate('translated-name', Locale::English);

        $repository->add($useProfile);

        $recoveredUseProfile = $repository->find($useProfile->id());

        $this->assertNotNull($recoveredUseProfile);
        $this->assertEquals($useProfile->id(), $recoveredUseProfile->id());
        $this->assertEquals('translated-name', $recoveredUseProfile->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $useProfile = UseProfileMother::registeredUseProfile();

        $useProfile->translate('translated-name', Locale::English);

        $repository->add($useProfile);

        $recoveredUseProfiles = $repository->findByFilter(new UseProfileFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredUseProfiles);
        $this->assertEquals($useProfile->id(), $recoveredUseProfiles[0]->id());
        $this->assertEquals('translated-name', $recoveredUseProfiles[0]->name());
    }

    public function testFindByFilterOrdersByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(UseProfileMother::fromName('B'));
        $repository->add(UseProfileMother::fromName('C'));
        $repository->add(UseProfileMother::fromName('A'));

        $filter = new UseProfileFilter();

        $this->assertSame(['A', 'B', 'C'], $this->names($repository->findByFilter($filter)));

        $filter->orderWay = 'DESC';

        $this->assertSame(['C', 'B', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByRegisteredAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->useProfile('A', registeredAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->useProfile('B', registeredAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->useProfile('C', registeredAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new UseProfileFilter();
        $filter->orderBy = 'registeredAt';

        $this->assertSame(['B', 'C', 'A'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterOrdersByUpdatedAt(): void
    {
        $repository = $this->createSUT();

        $repository->add($this->useProfile('A', updatedAt: new \DateTimeImmutable('2026-03-01')));
        $repository->add($this->useProfile('B', updatedAt: new \DateTimeImmutable('2026-01-01')));
        $repository->add($this->useProfile('C', updatedAt: new \DateTimeImmutable('2026-02-01')));

        $filter = new UseProfileFilter();
        $filter->orderBy = 'updatedAt';
        $filter->orderWay = 'DESC';

        $this->assertSame(['A', 'C', 'B'], $this->names($repository->findByFilter($filter)));
    }

    public function testFindByFilterPaginatesAndCountsTotal(): void
    {
        $repository = $this->createSUT();

        foreach (['A', 'B', 'C', 'D', 'E'] as $name) {
            $repository->add(UseProfileMother::fromName($name));
        }

        $filter = new UseProfileFilter(limit: 2);
        $filter->page = 1;

        $this->assertSame(['C', 'D'], $this->names($repository->findByFilter($filter)));
        $this->assertSame(5, $repository->totalResultsByFilter($filter));
    }

    private function useProfile(
        string $name,
        \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        ?\DateTimeImmutable $updatedAt = null,
    ): UseProfile {
        return new UseProfile(
            id: UseProfileId::generate(),
            name: $name,
            unit: Unit::Piece,
            registeredAt: $registeredAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * @param array<UseProfile> $useProfiles
     *
     * @return list<string>
     */
    private function names(array $useProfiles): array
    {
        return array_values(array_map(static fn(UseProfile $useProfile): string => $useProfile->name(), $useProfiles));
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): UseProfileRepository;
}
