<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\AI;

use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSectionKey;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTexts;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportTextsGenerator;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\EventCarbonFootprintReportData;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\OrganizationalCarbonFootprintReportData;
use App\CarbonFootprint\Infrastructure\AI\Gemini\GenerateEventReportTextsPrompt;
use App\CarbonFootprint\Infrastructure\AI\Gemini\GenerateOrganizationalReportTextsPrompt;
use App\CarbonFootprint\Infrastructure\AI\Gemini\TranslateReportTextsPrompt;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Infrastructure\AI\Gemini\GeminiClient;
use App\Shared\Infrastructure\AI\Gemini\GeminiPrompt;

final class GeminiCarbonFootprintReportTextsGenerator implements CarbonFootprintReportTextsGenerator
{
    private const int MAX_ATTEMPTS = 3;

    public function __construct(private readonly GeminiClient $geminiClient) {}

    public function generate(CarbonFootprintReportData $data, Locale $locale): CarbonFootprintReportTexts
    {
        $prompt = match (true) {
            $data instanceof EventCarbonFootprintReportData => new GenerateEventReportTextsPrompt($data, $locale),
            $data instanceof OrganizationalCarbonFootprintReportData => new GenerateOrganizationalReportTextsPrompt($data, $locale),
            default => throw new \InvalidArgumentException(sprintf('Unsupported report data "%s".', $data::class)),
        };

        return $this->requestTexts($prompt, CarbonFootprintReportSectionKey::requiredFor($data->subjectType()), CarbonFootprintReportSectionKey::optionalFor($data->subjectType()));
    }

    public function translate(CarbonFootprintReportTexts $texts, Locale $from, Locale $to): CarbonFootprintReportTexts
    {
        $keys = [];

        foreach (array_keys($texts->sections) as $keyValue) {
            $keys[] = CarbonFootprintReportSectionKey::from($keyValue);
        }

        return $this->requestTexts(new TranslateReportTextsPrompt($texts, $from, $to), $keys, []);
    }

    /**
     * @param list<CarbonFootprintReportSectionKey> $requiredKeys
     * @param list<CarbonFootprintReportSectionKey> $optionalKeys
     */
    private function requestTexts(GeminiPrompt $prompt, array $requiredKeys, array $optionalKeys): CarbonFootprintReportTexts
    {
        $lastFailure = '';

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; ++$attempt) {
            $texts = $this->textsFrom($this->geminiClient->send($prompt), $requiredKeys, $optionalKeys);

            if ($this->hasCorruptedCharacters($texts)) {
                $lastFailure = 'Gemini returned corrupted characters in the report texts';

                continue;
            }

            if (!$this->hasAllRequired($texts, $requiredKeys)) {
                $lastFailure = 'Gemini returned incomplete report texts';

                continue;
            }

            return $texts;
        }

        throw new \RuntimeException($lastFailure);
    }

    /** @param list<CarbonFootprintReportSectionKey> $requiredKeys */
    private function hasAllRequired(CarbonFootprintReportTexts $texts, array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if ('' === trim($texts->sections[$key->value] ?? '')) {
                return false;
            }
        }

        return true;
    }

    private function hasCorruptedCharacters(CarbonFootprintReportTexts $texts): bool
    {
        return 1 === preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', implode('', $texts->sections));
    }

    /**
     * @param array<mixed>                          $result
     * @param list<CarbonFootprintReportSectionKey> $requiredKeys
     * @param list<CarbonFootprintReportSectionKey> $optionalKeys
     */
    private function textsFrom(array $result, array $requiredKeys, array $optionalKeys): CarbonFootprintReportTexts
    {
        $sections = [];

        foreach ($requiredKeys as $key) {
            $sections[$key->value] = $this->cleanMarkdownContent($this->stringOrEmpty($result, $key->value));
        }

        foreach ($optionalKeys as $key) {
            $content = $this->cleanMarkdownContent($this->stringOrEmpty($result, $key->value));

            if ('' !== $content) {
                $sections[$key->value] = $content;
            }
        }

        return new CarbonFootprintReportTexts($sections);
    }

    /** @param array<mixed> $result */
    private function stringOrEmpty(array $result, string $key): string
    {
        return isset($result[$key]) && is_string($result[$key]) ? $result[$key] : '';
    }

    private function cleanMarkdownContent(string $content): string
    {
        $content = str_replace('\\n', "\n", $content);
        $content = str_replace('\\r', "\r", $content);
        $content = str_replace('\\t', "\t", $content);
        $content = str_replace('\\\\', '\\', $content);

        return trim($content);
    }
}
