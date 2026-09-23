<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GeneratePdf;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportCharts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportPdfGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\Shared\Domain\Model\Locale\Locale;

class FakeCarbonFootprintReportPdfGenerator implements CarbonFootprintReportPdfGenerator
{
    /** @var list<string> */
    private array $themeSlugs = [];

    /** @var list<CarbonFootprintReportTexts> */
    private array $texts = [];

    /** @var list<string> */
    private array $generatedPaths = [];

    public function __construct(private readonly string $name = 'default', private readonly ?Locale $failOn = null) {}

    public function generate(
        CarbonFootprintReportData $data,
        CarbonFootprintReportTexts $texts,
        CarbonFootprintReportCharts $charts,
        Locale $locale,
        string $themeSlug,
    ): string {
        if ($this->failOn === $locale) {
            throw new \RuntimeException('rendering failure');
        }

        $this->themeSlugs[] = $themeSlug;
        $this->texts[] = $texts;

        $path = tempnam(sys_get_temp_dir(), 'cfp-report-test-');
        assert(false !== $path);

        file_put_contents($path, sprintf('pdf-%s-%s', $this->name, $locale->value));

        $this->generatedPaths[] = $path;

        return $path;
    }

    public function name(): string
    {
        return $this->name;
    }

    /** @return list<string> */
    public function themeSlugs(): array
    {
        return $this->themeSlugs;
    }

    /** @return list<CarbonFootprintReportTexts> */
    public function texts(): array
    {
        return $this->texts;
    }

    /** @return list<string> */
    public function generatedPaths(): array
    {
        return $this->generatedPaths;
    }
}
