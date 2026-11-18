<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\Markdown;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;
use App\Changelog\Domain\Model\GuideArticle\InvalidGuideArticleException;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\Exception\InvalidFrontMatterException;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;

final class GuideArticleParser
{
    private const array DATE_FORMATS = ['!Y-m-d H:i', '!Y-m-d'];

    public function parse(string $absolutePath, GuideProfile $profile, GuideArticlePath $path): GuideArticle
    {
        $name = sprintf('%s/%s.md', $profile->value, (string) $path);
        $contents = @file_get_contents($absolutePath);

        if (false === $contents) {
            throw new InvalidGuideArticleException(sprintf('Guide article file "%s" is invalid: unable to read file', $name));
        }

        try {
            $parsed = (new FrontMatterParser(new SymfonyYamlFrontMatterParser()))->parse($contents);
        } catch (InvalidFrontMatterException $exception) {
            throw new InvalidGuideArticleException(sprintf('Guide article file "%s" is invalid: %s', $name, $exception->getMessage()));
        }

        $frontMatter = $parsed->getFrontMatter();

        if (!is_array($frontMatter)) {
            throw new InvalidGuideArticleException(sprintf('Guide article file "%s" is invalid: missing frontmatter', $name));
        }

        /* @var array<string, mixed> $frontMatter */
        return new GuideArticle(
            profile: $profile,
            path: $path,
            title: $this->requireNonEmptyString($frontMatter, 'title', $name),
            summary: $this->requireNonEmptyString($frontMatter, 'summary', $name),
            menu: $this->optionalString($frontMatter, 'menu') ?? '',
            body: trim($parsed->getContent()),
            updated: $this->parseDate($frontMatter),
        );
    }

    /**
     * @param array<string, mixed> $frontMatter
     */
    private function requireNonEmptyString(array $frontMatter, string $key, string $name): string
    {
        $value = $frontMatter[$key] ?? null;

        if (!is_string($value) || '' === trim($value)) {
            throw new InvalidGuideArticleException(sprintf('Guide article file "%s" is invalid: "%s" is required and must be a non-empty string', $name, $key));
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
    private function parseDate(array $frontMatter): ?\DateTimeImmutable
    {
        $value = $frontMatter['updated'] ?? null;

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }

        if (is_int($value)) {
            return new \DateTimeImmutable('@' . $value);
        }

        if (is_string($value)) {
            foreach (self::DATE_FORMATS as $format) {
                $date = \DateTimeImmutable::createFromFormat($format, trim($value));

                if (false !== $date) {
                    return $date;
                }
            }
        }

        return null;
    }
}
