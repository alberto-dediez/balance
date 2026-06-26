<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Domain\Model\Material;

use App\CarbonFootprint\Domain\Model\Material\MaterialFilter;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\Shared\Infrastructure\Locale\FakeLocaleSetter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class MaterialRepositoryTest extends KernelTestCase
{
    public function setUp(): void
    {
        FakeLocaleSetter::reset();
        parent::setUp();
    }

    public function testAddMaterialToRepository(): void
    {
        $repository = $this->createSUT();

        $material = MaterialMother::registeredMaterial();

        $this->assertNull($repository->find($material->id()));

        $repository->add($material);

        $recoveredMaterial = $repository->find($material->id());

        $this->assertNotNull($recoveredMaterial);
        $this->assertEquals($material->id(), $recoveredMaterial->id());
    }

    public function testRemoveMaterialToRepository(): void
    {
        $repository = $this->createSUT();

        $material = MaterialMother::registeredMaterial();

        $repository->add($material);

        $recoveredMaterial = $repository->find($material->id());

        $this->assertNotNull($recoveredMaterial);
        $this->assertEquals($material->id(), $recoveredMaterial->id());

        $repository->remove($material);

        $this->assertNull($repository->find($material->id()));
    }

    public function testFindByFilter(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::fromName('Nombre específico'));

        $filter = new MaterialFilter();

        $resultUnfiltered = $repository->findByFilter($filter);

        $this->assertCount(6, $resultUnfiltered);

        $filter = new MaterialFilter(
            name: 'Nombre específico',
        );

        $result = $repository->findByFilter($filter);

        $this->assertCount(1, $result);
    }

    public function testFindByFilterWithLimitZeroReturnAllElements(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::fromName('Nombre específico'));

        $filter = new MaterialFilter(limit: 0);

        $result = $repository->findByFilter($filter);

        $this->assertCount(6, $result);
    }

    public function testFindNotRecoverRemoved(): void
    {
        $repository = $this->createSUT();

        $material = MaterialMother::registeredMaterial();

        $repository->add($material);

        $recoveredMaterial = $repository->find($material->id());

        $this->assertNotNull($recoveredMaterial);

        $recoveredMaterial->remove();

        $repository->add($material);

        $this->assertNull($repository->find($material->id()));
    }

    public function testFindRecoverWithTranslation(): void
    {
        $repository = $this->createSUT(Locale::English);

        $material = MaterialMother::registeredMaterial();

        $this->assertNull($repository->find($material->id()));

        $material->translate('translated-name', Locale::English);

        $repository->add($material);

        $recoveredMaterial = $repository->find($material->id());

        $this->assertNotNull($recoveredMaterial);
        $this->assertEquals($material->id(), $recoveredMaterial->id());
        $this->assertEquals('translated-name', $recoveredMaterial->name());
    }

    public function testFindByFilterWithNameTranslated(): void
    {
        $repository = $this->createSUT(Locale::English);

        $material = MaterialMother::registeredMaterial();

        $material->translate('translated-name', Locale::English);

        $repository->add($material);

        $recoveredMaterials = $repository->findByFilter(new MaterialFilter(name: 'translated-name'));

        $this->assertCount(1, $recoveredMaterials);
        $this->assertEquals($material->id(), $recoveredMaterials[0]->id());
        $this->assertEquals('translated-name', $recoveredMaterials[0]->name());
    }

    public function testFindOneByName(): void
    {
        $repository = $this->createSUT();

        $repository->add(MaterialMother::random());
        $repository->add(MaterialMother::fromName('Nombre específico'));

        $found = $repository->findOneByName('Nombre específico');

        $this->assertNotNull($found);
        $this->assertEquals('Nombre específico', $found->name());

        $this->assertNull($repository->findOneByName('No existe'));
    }

    abstract protected function createSUT(Locale $locale = Locale::Spanish): MaterialRepository;
}
