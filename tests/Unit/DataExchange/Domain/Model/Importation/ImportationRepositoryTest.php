<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Domain\Model\Importation;

use App\DataExchange\Domain\Model\Importation\ImportationFile;
use App\DataExchange\Domain\Model\Importation\ImportationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class ImportationRepositoryTest extends KernelTestCase
{
    public function testWhenSaveAnImportationThenICanGetHisColumnsAndData(): void
    {
        $name = 'file-to-import.csv';
        $path = __DIR__ . '/file-to-import.csv';

        $importationFile = new ImportationFile(
            path: $path,
        );

        $importation = ImportationMother::fromNameAndFile($name, $importationFile);

        $repository = $this->createSUT();

        $repository->add($importation);

        $importation = $repository->find($importation->id());

        $configurations = $importation->configurations();

        $this->assertCount(4, $configurations);

        $data = $repository->getData($importation->id(), 2);

        $this->assertEquals('Carla Ruiz', $data['nombre']);
        $this->assertEquals('15', $data['edad']);
        $this->assertEquals('Clase Beta', $data['clase']);
        $this->assertEquals('Invisibilidad', $data['superpoder']);

        $values = $configurations[2]->values;

        $this->assertCount(4, $values);
        $this->assertTrue(in_array('Clase Alpha', $values));
        $this->assertTrue(in_array('Clase Beta', $values));
        $this->assertTrue(in_array('Clase Gamma', $values));
        $this->assertTrue(in_array('Clase Omega', $values));
    }

    abstract public function createSUT(): ImportationRepository;
}
