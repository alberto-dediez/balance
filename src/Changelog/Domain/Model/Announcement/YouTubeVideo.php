<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

final readonly class YouTubeVideo implements \Stringable
{
    private const string ID_PATTERN = '/^[A-Za-z0-9_-]{11}\z/';

    private const array HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com'];

    private const array PATH_PREFIXES = ['shorts', 'embed', 'live'];

    private function __construct(public string $id) {}

    public static function fromUrl(string $url): self
    {
        $id = self::extractId($url);

        if (null === $id) {
            throw new InvalidYouTubeUrlException($url);
        }

        return new self($id);
    }

    public static function fromId(string $id): self
    {
        if (1 !== preg_match(self::ID_PATTERN, $id)) {
            throw new InvalidYouTubeUrlException($id);
        }

        return new self($id);
    }

    public static function isValidUrl(string $url): bool
    {
        return null !== self::extractId($url);
    }

    public function embedUrl(): string
    {
        return sprintf('https://www.youtube-nocookie.com/embed/%s', $this->id);
    }

    public function watchUrl(): string
    {
        return sprintf('https://www.youtube.com/watch?v=%s', $this->id);
    }

    public function __toString(): string
    {
        return $this->id;
    }

    private static function extractId(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (false === $parts || !isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);

        if (!in_array($host, self::HOSTS, true)) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $parts['path'] ?? ''), static fn(string $segment): bool => '' !== $segment));

        $candidate = match (true) {
            'youtu.be' === $host => $segments[0] ?? null,
            ['watch'] === $segments => self::queryParameter($parts['query'] ?? '', 'v'),
            2 === count($segments) && in_array($segments[0], self::PATH_PREFIXES, true) => $segments[1],
            default => null,
        };

        if (null === $candidate || 1 !== preg_match(self::ID_PATTERN, $candidate)) {
            return null;
        }

        return $candidate;
    }

    private static function queryParameter(string $query, string $name): ?string
    {
        parse_str($query, $parameters);

        $value = $parameters[$name] ?? null;

        return is_string($value) ? $value : null;
    }
}
