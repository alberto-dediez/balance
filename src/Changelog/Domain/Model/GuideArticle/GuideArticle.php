<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\GuideArticle;

final class GuideArticle
{
    public function __construct(
        public readonly GuideProfile $profile,
        public readonly GuideArticlePath $path,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $menu,
        public readonly string $body,
        public readonly ?\DateTimeImmutable $updated = null,
    ) {}

    public static function placeholder(GuideProfile $profile): self
    {
        return new self(
            profile: $profile,
            path: new GuideArticlePath('placeholder'),
            title: '',
            summary: '',
            menu: '',
            body: '',
        );
    }

    public function matches(GuideArticleFilter $filter): bool
    {
        if ($this->profile !== $filter->profile) {
            return false;
        }

        $words = self::words($filter->text);

        if ([] === $words) {
            return true;
        }

        $haystack = self::normalize(implode(' ', [$this->title, $this->summary, $this->menu, $this->body]));

        foreach ($words as $word) {
            if (!str_contains($haystack, $word)) {
                return false;
            }
        }

        return true;
    }

    public function excerpt(string $text, int $context = 150): ?string
    {
        $body = trim((string) preg_replace('/\s+/', ' ', $this->body));
        $normalized = '';
        $origins = [];

        foreach (mb_str_split($body) as $index => $character) {
            $folded = self::fold($character);
            $normalized .= $folded;
            array_push($origins, ...array_fill(0, mb_strlen($folded), $index));
        }

        foreach (self::words($text) as $word) {
            $position = mb_strpos($normalized, $word);

            if (false === $position) {
                continue;
            }

            $first = $origins[$position];
            $last = $origins[$position + mb_strlen($word) - 1];
            $start = max(0, $first - $context);
            $length = min(mb_strlen($body) - $start, $last - $start + 1 + $context);

            return trim(mb_substr($body, $start, $length));
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function words(?string $text): array
    {
        $normalized = self::normalize($text ?? '');

        return '' === $normalized ? [] : explode(' ', $normalized);
    }

    private static function normalize(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', self::fold($value)) ?? '');
    }

    private static function fold(string $value): string
    {
        $decomposed = \Normalizer::normalize($value, \Normalizer::FORM_D);
        $withoutMarks = preg_replace('/\p{Mn}/u', '', false === $decomposed ? $value : $decomposed) ?? $value;

        return mb_strtolower($withoutMarks);
    }
}
