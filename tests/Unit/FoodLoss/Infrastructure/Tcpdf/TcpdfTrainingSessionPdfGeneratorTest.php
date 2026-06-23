<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Tcpdf;

use App\FoodLoss\Domain\Model\TrainingAttendance\TrainingSessionPdfGenerator;
use App\FoodLoss\Infrastructure\Tcpdf\TcpdfTrainingSessionPdfGenerator;
use App\Tests\Unit\FoodLoss\Domain\Model\TrainingSession\TrainingSessionMother;
use App\Tests\Unit\HumanResources\Domain\Model\Employee\EmployeeMother;
use App\Tests\Unit\Shared\Infrastructure\Symfony\Translation\FakeTranslator;
use PHPUnit\Framework\TestCase;

class TcpdfTrainingSessionPdfGeneratorTest extends TestCase
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

    public function testImplementsTrainingSessionPdfGenerator(): void
    {
        $this->assertInstanceOf(TrainingSessionPdfGenerator::class, $this->createSUT());
    }

    public function testGenerateReturnsPathToPdfFile(): void
    {
        $session = TrainingSessionMother::registeredTrainingSession();
        $employee = EmployeeMother::registeredEmployee();

        $path = $this->createSUT()->generate($session, $employee);

        $this->generatedFiles[] = $path;

        $this->assertStringStartsWith(sys_get_temp_dir() . '/training-sessions/', $path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    public function testGeneratedFileExistsOnDisk(): void
    {
        $session = TrainingSessionMother::registeredTrainingSession();
        $employee = EmployeeMother::registeredEmployee();

        $path = $this->createSUT()->generate($session, $employee);

        $this->generatedFiles[] = $path;

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function testGenerateReturnsDifferentPathsForConsecutiveCalls(): void
    {
        $session = TrainingSessionMother::registeredTrainingSession();
        $employee = EmployeeMother::registeredEmployee();

        $sut = $this->createSUT();

        $path1 = $sut->generate($session, $employee);
        $path2 = $sut->generate($session, $employee);

        $this->generatedFiles[] = $path1;
        $this->generatedFiles[] = $path2;

        $this->assertNotSame($path1, $path2);
    }

    private function createSUT(): TcpdfTrainingSessionPdfGenerator
    {
        return new TcpdfTrainingSessionPdfGenerator(
            translator: new FakeTranslator(),
            uploadsDir: sys_get_temp_dir(),
        );
    }
}
