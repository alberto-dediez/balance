<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\EventCarbonFootprintReport\Generate;

use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\EventCarbonFootprintReport\EventCarbonFootprintReportTextsGenerator;
use App\Shared\Domain\Model\Locale\Locale;

class FakeReportTextsGenerator implements EventCarbonFootprintReportTextsGenerator
{
    private bool $shouldFail = false;

    /** @var list<Locale> */
    private array $generateCalls = [];

    /** @var list<Locale> */
    private array $translateCalls = [];

    public function failOnGenerate(): void
    {
        $this->shouldFail = true;
    }

    public function generate(EventCarbonFootprintReportData $data, Locale $locale): EventCarbonFootprintReportTexts
    {
        $this->generateCalls[] = $locale;

        if ($this->shouldFail) {
            throw new \RuntimeException('Gemini failure');
        }

        return $this->texts($locale);
    }

    public function translate(EventCarbonFootprintReportTexts $texts, Locale $from, Locale $to): EventCarbonFootprintReportTexts
    {
        $this->translateCalls[] = $to;

        return $this->texts($to);
    }

    /** @return list<Locale> */
    public function generateCalls(): array
    {
        return $this->generateCalls;
    }

    /** @return list<Locale> */
    public function translateCalls(): array
    {
        return $this->translateCalls;
    }

    private function texts(Locale $locale): EventCarbonFootprintReportTexts
    {
        return new EventCarbonFootprintReportTexts(
            executiveSummary: 'summary-' . $locale->value,
            methodology: 'methodology-' . $locale->value,
            emissionFactorSources: 'sources-' . $locale->value,
            analysisAndRecommendations: 'analysis-' . $locale->value,
        );
    }
}
