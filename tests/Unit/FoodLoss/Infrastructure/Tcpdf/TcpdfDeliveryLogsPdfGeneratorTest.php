<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Tcpdf;

use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogsPdfGenerator;
use App\FoodLoss\Domain\Model\RecipientOrganization\PickupType;
use App\FoodLoss\Infrastructure\Symfony\Twig\RecipientOrganizationExtension;
use App\FoodLoss\Infrastructure\Tcpdf\TcpdfDeliveryLogsPdfGenerator;
use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use App\Tests\Unit\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\LocaleSwitcher;

class TcpdfDeliveryLogsPdfGeneratorTest extends TestCase
{
    /** @var string[] */
    private array $generatedFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->generatedFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testImplementsDeliveryLogsPdfGenerator(): void
    {
        $this->assertInstanceOf(DeliveryLogsPdfGenerator::class, $this->createSUT());
    }

    public function testGenerateReturnsPathToPdfFile(): void
    {
        $deliveryLog = DeliveryLogMother::registeredDeliveryLog();
        $deliverAt = new \DateTimeImmutable('2026-06-01');

        $path = $this->createSUT()->generate([$deliveryLog], $deliverAt, PickupType::Pickup);

        $this->generatedFiles[] = $path;

        $this->assertStringStartsWith(sys_get_temp_dir() . '/delivery-logs/', $path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function testGeneratedFileExistsOnDisk(): void
    {
        $deliveryLog = DeliveryLogMother::registeredDeliveryLog();
        $deliverAt = new \DateTimeImmutable('2026-06-01');

        $path = $this->createSUT()->generate([$deliveryLog], $deliverAt, PickupType::Pickup);

        $this->generatedFiles[] = $path;

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function testGenerateReturnsDifferentPathsForConsecutiveCalls(): void
    {
        $deliveryLog = DeliveryLogMother::registeredDeliveryLog();
        $deliverAt = new \DateTimeImmutable('2026-06-01');

        $sut = $this->createSUT();

        $path1 = $sut->generate([$deliveryLog], $deliverAt, PickupType::Pickup);
        $path2 = $sut->generate([$deliveryLog], $deliverAt, PickupType::Pickup);

        $this->generatedFiles[] = $path1;
        $this->generatedFiles[] = $path2;

        $this->assertNotSame($path1, $path2);
    }

    private function createSUT(): TcpdfDeliveryLogsPdfGenerator
    {
        $recipientOrganizationExtension = $this->createMock(RecipientOrganizationExtension::class);
        $recipientOrganizationExtension->method('recipientOrganizationName')->willReturn(null);

        $propertyExtension = $this->createMock(PropertyExtension::class);
        $propertyExtension->method('propertyName')->willReturn(null);

        $localeSwitcher = $this->createMock(LocaleSwitcher::class);
        $localeSwitcher->method('runWithLocale')->willReturnCallback(
            static function (string $locale, callable $callback): mixed {
                return $callback();
            },
        );

        return new TcpdfDeliveryLogsPdfGenerator(
            recipientOrganizationExtension: $recipientOrganizationExtension,
            propertyExtension: $propertyExtension,
            translator: new FakeTranslator(),
            localeSwitcher: $localeSwitcher,
            uploadsDir: sys_get_temp_dir(),
        );
    }
}
