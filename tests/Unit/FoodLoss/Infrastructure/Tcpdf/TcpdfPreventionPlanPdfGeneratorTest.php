<?php

declare(strict_types=1);

namespace App\Tests\Unit\FoodLoss\Infrastructure\Tcpdf;

use App\FoodLoss\Infrastructure\Tcpdf\TcpdfPreventionPlanPdfGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanMother;
use PHPUnit\Framework\TestCase;

class TcpdfPreventionPlanPdfGeneratorTest extends TestCase
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

    public function testGenerateReturnsPathWithCorrectFormat(): void
    {
        $plan = PreventionPlanMother::withDraftApprovedStatus('Test Plan', 'plan-test-id', 'account-id');
        $version = $plan->currentPlanVersion();

        $path = $this->createSUT()->generate($plan, $version, Locale::Spanish);

        $this->generatedFiles[] = $path;

        $this->assertStringStartsWith('/tmp/', $path);
        $this->assertStringEndsWith('-plan.pdf', $path);
        $this->assertStringContainsString((string) $plan->id(), $path);
        $this->assertStringContainsString('-v' . $version->versionNumber() . '-es-', $path);
    }

    public function testGeneratedFileExistsOnDisk(): void
    {
        $plan = PreventionPlanMother::withDraftApprovedStatus('Test Plan', 'plan-disk-test-id', 'account-id');
        $version = $plan->currentPlanVersion();

        $path = $this->createSUT()->generate($plan, $version, Locale::Spanish);

        $this->generatedFiles[] = $path;

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    public function testGenerateWithNoSectionsCreatesFile(): void
    {
        $plan = PreventionPlanMother::withConfiguredStatus('Empty Plan', 'plan-empty-id', 'account-id');
        $version = $plan->currentPlanVersion();

        $path = $this->createSUT()->generate($plan, $version, Locale::Spanish);

        $this->generatedFiles[] = $path;

        $this->assertFileExists($path);
    }

    public function testGenerateWithSectionsOutOfOrderStillGenerates(): void
    {
        $plan = PreventionPlanMother::withGeneratedSections('Unordered Plan', 'plan-unordered-id', 'account-id');
        $plan->addSection('Section C', 'Content C', 4);
        $plan->addSection('Section A', 'Content A', 2);
        $plan->addSection('Section B', 'Content B', 3);
        $version = $plan->currentPlanVersion();

        $path = $this->createSUT()->generate($plan, $version, Locale::Spanish);

        $this->generatedFiles[] = $path;

        $this->assertFileExists($path);
    }

    public function testGenerateReturnsDifferentPathsForDifferentPlans(): void
    {
        $plan1 = PreventionPlanMother::withDraftApprovedStatus('Plan One', 'plan-id-one', 'account-id');
        $plan2 = PreventionPlanMother::withDraftApprovedStatus('Plan Two', 'plan-id-two', 'account-id');

        $sut = $this->createSUT();

        $path1 = $sut->generate($plan1, $plan1->currentPlanVersion(), Locale::Spanish);
        $path2 = $sut->generate($plan2, $plan2->currentPlanVersion(), Locale::Spanish);

        $this->generatedFiles[] = $path1;
        $this->generatedFiles[] = $path2;

        $this->assertNotSame($path1, $path2);
    }

    public function testGenerateWithMarkdownContentCreatesFile(): void
    {
        $plan = PreventionPlanMother::withConfiguredStatus('Markdown Plan', 'plan-markdown-id', 'account-id');
        $plan->markAsGenerated();

        $plan->addSection('Introducción', "## Título\n\nEste es un párrafo con **negrita** y _cursiva_.\n\n- Item 1\n- Item 2", 0);
        $version = $plan->currentPlanVersion();

        $path = $this->createSUT()->generate($plan, $version, Locale::Spanish);

        $this->generatedFiles[] = $path;

        $this->assertFileExists($path);
    }

    private function createSUT(): TcpdfPreventionPlanPdfGenerator
    {
        return new TcpdfPreventionPlanPdfGenerator();
    }
}
