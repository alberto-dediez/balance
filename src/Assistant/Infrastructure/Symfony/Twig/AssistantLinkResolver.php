<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Symfony\Twig;

use App\Shared\Application\Assistant\ToolLink;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AssistantLinkResolver
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    public function isReference(string $href): bool
    {
        return str_starts_with($href, ToolLink::SCHEME);
    }

    public function isInternal(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (!is_string($host) || '' === $host) {
            return false;
        }

        return 0 === strcasecmp($host, $this->urlGenerator->getContext()->getHost());
    }

    public function resolve(string $href, bool $absolute = false): ?string
    {
        if (!$this->isReference($href)) {
            return null;
        }

        $reference = substr($href, strlen(ToolLink::SCHEME));
        $separator = strpos($reference, '?');
        $name = false === $separator ? $reference : substr($reference, 0, $separator);
        $query = false === $separator ? '' : substr($reference, $separator + 1);

        if ('' === $name || str_starts_with($name, '_')) {
            return null;
        }

        parse_str($query, $raw);
        $parameters = [];

        foreach ($raw as $key => $value) {
            if (!is_string($value)) {
                return null;
            }

            $parameters[(string) $key] = $value;
        }

        try {
            $url = $this->urlGenerator->generate(
                $name,
                $parameters,
                $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH,
            );
        } catch (ExceptionInterface) {
            return null;
        }

        return explode('?', $url, 2)[0];
    }
}
