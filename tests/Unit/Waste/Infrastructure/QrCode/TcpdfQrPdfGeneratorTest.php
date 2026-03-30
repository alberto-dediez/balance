<?php

declare(strict_types=1);

namespace App\Tests\Unit\Waste\Infrastructure\QrCode;

use App\Property\Domain\Model\Property\PropertyRepository;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyMother;
use App\Tests\Unit\Property\Domain\Model\Property\PropertyRepositoryMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use App\Tests\Unit\Waste\Domain\Model\Container\ContainerMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteMother;
use App\Tests\Unit\Waste\Domain\Model\Waste\WasteRepositoryMother;
use App\Waste\Domain\Model\Waste\WasteRepository;
use App\Waste\Infrastructure\QrCode\TcpdfQrPdfGenerator;
use PHPUnit\Framework\TestCase;

class TcpdfQrPdfGeneratorTest extends TestCase
{
    public function testGenerateReturnsPath(): void
    {
        $waste = WasteMother::random();
        $wasteRepository = WasteRepositoryMother::inMemory();
        $wasteRepository->add($waste);

        $property = PropertyMother::random();
        $propertyRepository = PropertyRepositoryMother::inMemory();
        $propertyRepository->add($property);

        $container = ContainerMother::fromPropertyAndWaste((string) $property->id(), (string) $waste->id());

        $sut = $this->createSUT($wasteRepository, $propertyRepository);

        self::assertNotEmpty($container->qrCode());

        $path = $sut->generate($container, $container->qrCode());

        $this->assertNotEmpty($path);
    }

    private function createSUT(WasteRepository $wasteRepository, PropertyRepository $propertyRepository): TcpdfQrPdfGenerator
    {
        return new TcpdfQrPdfGenerator(
            translator: new FakeTranslator(),
            wasteRepository: $wasteRepository,
            propertyRepository: $propertyRepository,
        );
    }
}
