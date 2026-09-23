<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Infrastructure\Tcpdf;

use App\CarbonFootprint\Infrastructure\Tcpdf\ThemeAwareCarbonFootprintReportPdfGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf\FakeCarbonFootprintReportPdfGenerator;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportChartsMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsMother;
use App\Tests\Unit\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportDataMother;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ThemeAwareCarbonFootprintReportPdfGeneratorTest extends TestCase
{
    private FakeCarbonFootprintReportPdfGenerator $inner;
    private FakeCarbonFootprintReportPdfGenerator $classic;
    private ThemeAwareCarbonFootprintReportPdfGenerator $sut;

    protected function setUp(): void
    {
        $this->inner = new FakeCarbonFootprintReportPdfGenerator('default');
        $this->classic = new FakeCarbonFootprintReportPdfGenerator('classic');

        $this->sut = new ThemeAwareCarbonFootprintReportPdfGenerator(
            $this->inner,
            new ServiceLocator([
                'classic' => fn() => $this->classic,
                'broken' => fn() => new \stdClass(),
            ]),
        );
    }

    /** @return iterable<string, array{0: string, 1: string}> */
    public static function themes(): iterable
    {
        yield 'default theme' => ['default', 'default'];
        yield 'classic theme' => ['classic', 'classic'];
        yield 'theme without generator' => ['aurora', 'default'];
        yield 'theme whose service is not a generator' => ['broken', 'default'];
    }

    /** @dataProvider themes */
    public function testItPicksTheGeneratorOfTheTheme(string $themeSlug, string $expected): void
    {
        $path = $this->sut->generate(EventCarbonFootprintReportDataMother::random(), CarbonFootprintReportTextsMother::forEvent(), CarbonFootprintReportChartsMother::withFixturePng(), Locale::Spanish, $themeSlug);

        try {
            $this->assertSame('pdf-' . $expected . '-es', (string) file_get_contents($path));
        } finally {
            @unlink($path);
        }
    }
}
