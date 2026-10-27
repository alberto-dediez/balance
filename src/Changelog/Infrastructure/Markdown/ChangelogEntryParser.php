<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Markdown;

use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\InvalidChangelogEntryException;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\Exception\InvalidFrontMatterException;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;

final class ChangelogEntryParser
{
    private const string FILE_NAME_PATTERN = '/^\d{4}-\d{2}-\d{2}-(?<slug>.+)$/';

    private const string MANUAL_NAME_PATTERN = '/^[a-z0-9][a-z0-9._-]*$/i';

    private const array DATE_FORMATS = ['!Y-m-d H:i:s', '!Y-m-d H:i', '!Y-m-d'];

    public function parse(string $absolutePath, string $fileName): ChangelogEntry
    {
        $contents = @file_get_contents($absolutePath);

        if (false === $contents) {
            throw new InvalidChangelogEntryException($fileName, 'unable to read file');
        }

        $parser = new FrontMatterParser(new SymfonyYamlFrontMatterParser());

        try {
            $parsed = $parser->parse($contents);
        } catch (InvalidFrontMatterException $exception) {
            throw new InvalidChangelogEntryException($fileName, $exception->getMessage());
        }

        $frontMatter = $parsed->getFrontMatter();

        if (!is_array($frontMatter)) {
            throw new InvalidChangelogEntryException($fileName, 'missing frontmatter');
        }

        /** @var array<string, mixed> $frontMatter */
        $body = $parsed->getContent();

        $slug = $this->extractSlug($fileName);

        $title = $this->requireNonEmptyString($frontMatter, 'title', $fileName);
        $date = $this->parseDate($frontMatter, $fileName);
        $category = $this->parseCategory($frontMatter, $fileName);
        $summary = $this->requireNonEmptyString($frontMatter, 'summary', $fileName);

        $version = $this->optionalString($frontMatter, 'version');
        $plan = $this->optionalString($frontMatter, 'plan');
        $manual = $this->optionalFileName($frontMatter, 'manual', $fileName);
        $techManual = $this->optionalFileName($frontMatter, 'techManual', $fileName);
        $published = $this->optionalBool($frontMatter, 'published') ?? true;

        return new ChangelogEntry(
            slug: new ChangelogEntrySlug($slug),
            title: $title,
            date: $date,
            category: $category,
            summary: $summary,
            body: trim($body),
            version: $version,
            planSlug: $plan,
            manualFileName: $manual,
            technicalManualFileName: $techManual,
            published: $published,
        );
    }

    private function extractSlug(string $fileName): string
    {
        $nameWithoutExtension = preg_replace('/\.md$/', '', $fileName) ?? $fileName;

        if (1 === preg_match(self::FILE_NAME_PATTERN, $nameWithoutExtension, $matches)) {
            return $matches['slug'];
        }

        return $nameWithoutExtension;
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function requireNonEmptyString(array $frontMatter, string $key, string $fileName): string
    {
        $value = $frontMatter[$key] ?? null;

        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidChangelogEntryException($fileName, sprintf('"%s" is required and must be a non-empty string', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function optionalString(array $frontMatter, string $key): ?string
    {
        $value = $frontMatter[$key] ?? null;

        return is_string($value) && '' !== trim($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function optionalBool(array $frontMatter, string $key): ?bool
    {
        $value = $frontMatter[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function optionalFileName(array $frontMatter, string $key, string $fileName): ?string
    {
        $value = $this->optionalString($frontMatter, $key);

        if (null === $value) {
            return null;
        }

        if (str_contains($value, '/') || str_contains($value, '\\') || str_contains($value, '..') || 1 !== preg_match(self::MANUAL_NAME_PATTERN, $value)) {
            throw new InvalidChangelogEntryException($fileName, sprintf('"%s" must be a plain file name without path separators', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function parseDate(array $frontMatter, string $fileName): \DateTimeImmutable
    {
        $value = $frontMatter['date'] ?? null;

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (is_int($value)) {
            $value = (new \DateTimeImmutable('@' . $value))->format('Y-m-d H:i:s');
        }

        if (is_string($value) && '' !== trim($value)) {
            foreach (self::DATE_FORMATS as $format) {
                $date = \DateTimeImmutable::createFromFormat($format, trim($value));

                if (false !== $date) {
                    return $date;
                }
            }
        }

        throw new InvalidChangelogEntryException($fileName, '"date" is required and must be a parseable "YYYY-MM-DD" or "YYYY-MM-DD HH:MM" date');
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function parseCategory(array $frontMatter, string $fileName): ChangelogCategory
    {
        $value = $frontMatter['category'] ?? null;

        if (!is_string($value)) {
            throw new InvalidChangelogEntryException($fileName, '"category" is required');
        }

        $category = ChangelogCategory::tryFrom($value);

        if (!$category instanceof ChangelogCategory) {
            throw new InvalidChangelogEntryException($fileName, sprintf('"category" value "%s" is not one of the allowed categories', $value));
        }

        return $category;
    }
}
