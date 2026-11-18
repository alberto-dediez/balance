<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\GuideArticle;

final readonly class GuideArticlePath implements \Stringable
{
    private const string PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*(\/[a-z0-9]+(-[a-z0-9]+)*){0,3}\z/';

    private const int MAX_LENGTH = 200;

    public string $value;

    public function __construct(string $value)
    {
        if (strlen($value) > self::MAX_LENGTH || 'index' === $value || 1 !== preg_match(self::PATTERN, $value)) {
            throw new InvalidGuideArticleException(sprintf('"%s" is not a valid guide article path', $value));
        }

        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function resolve(string $relativeLink): ?self
    {
        if ('' === $relativeLink || str_contains($relativeLink, ':') || str_starts_with($relativeLink, '/') || !str_ends_with($relativeLink, '.md')) {
            return null;
        }

        $segments = explode('/', $this->value);
        array_pop($segments);

        foreach (explode('/', substr($relativeLink, 0, -3)) as $part) {
            if ('..' === $part) {
                if ([] === $segments) {
                    return null;
                }

                array_pop($segments);

                continue;
            }

            if ('.' === $part) {
                continue;
            }

            $segments[] = $part;
        }

        try {
            return new self(implode('/', $segments));
        } catch (InvalidGuideArticleException) {
            return null;
        }
    }
}
