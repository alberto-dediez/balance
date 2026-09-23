<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\CarbonFootprintReport\GenerateTexts;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsGenerator;
use App\Shared\Domain\Model\Locale\Locale;

class FakeCarbonFootprintReportTextsGenerator implements CarbonFootprintReportTextsGenerator
{
    private bool $shouldFail = false;

    private bool $incomplete = false;

    /** @var list<Locale> */
    private array $generateCalls = [];

    /** @var list<Locale> */
    private array $translateCalls = [];

    private ?CarbonFootprintReportData $lastData = null;

    public function failOnGenerate(): void
    {
        $this->shouldFail = true;
    }

    public function returnIncompleteTexts(): void
    {
        $this->incomplete = true;
    }

    public function generate(CarbonFootprintReportData $data, Locale $locale): CarbonFootprintReportTexts
    {
        $this->generateCalls[] = $locale;
        $this->lastData = $data;

        if ($this->shouldFail) {
            throw new \RuntimeException('Gemini failure');
        }

        $sections = [];

        foreach (CarbonFootprintReportSectionKey::requiredFor($data->subjectType()) as $index => $key) {
            $sections[$key->value] = $this->incomplete && 0 === $index ? '' : $key->value . '-' . $locale->value;
        }

        return new CarbonFootprintReportTexts($sections);
    }

    public function translate(CarbonFootprintReportTexts $texts, Locale $from, Locale $to): CarbonFootprintReportTexts
    {
        $this->translateCalls[] = $to;

        $sections = [];

        foreach ($texts->sections as $key => $content) {
            $sections[$key] = $content . '-' . $to->value;
        }

        return new CarbonFootprintReportTexts($sections);
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

    public function lastData(): ?CarbonFootprintReportData
    {
        return $this->lastData;
    }
}
