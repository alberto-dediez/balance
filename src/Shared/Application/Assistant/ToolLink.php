<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

final readonly class ToolLink
{
    public const string SCHEME = 'route:';

    /** @param list<string> $parameters */
    public function __construct(
        public string $label,
        public string $route,
        public array $parameters = [],
    ) {}

    /** @param array<string, string> $parameters */
    public static function href(string $route, array $parameters = []): string
    {
        return self::SCHEME . $route . ([] === $parameters ? '' : '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986));
    }

    /** @param array<string, string> $references */
    public function hrefFrom(array $references): ?string
    {
        $parameters = [];

        foreach ($this->parameters as $name) {
            if (!isset($references[$name])) {
                return null;
            }

            $parameters[$name] = $references[$name];
        }

        return self::href($this->route, $parameters);
    }
}
