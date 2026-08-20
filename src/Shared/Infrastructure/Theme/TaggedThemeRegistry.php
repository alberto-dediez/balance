<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Theme;

use App\Shared\Domain\Model\Theme\Theme;
use App\Shared\Domain\Model\Theme\ThemeRegistry;
use Psr\Log\LoggerInterface;

final class TaggedThemeRegistry implements ThemeRegistry
{
    /** @var array<string, Theme> */
    private array $themes = [];

    /**
     * @param iterable<Theme> $themes
     */
    public function __construct(iterable $themes, private readonly LoggerInterface $logger, private readonly DefaultTheme $defaultTheme)
    {
        foreach ($themes as $theme) {
            $this->themes[$theme->slug()] = $theme;
        }

        $this->themes[$this->defaultTheme->slug()] = $this->defaultTheme;
    }

    /** @return list<Theme> */
    public function all(): array
    {
        $themes = $this->themes;

        uasort($themes, static fn(Theme $a, Theme $b): int => $a->name() <=> $b->name());

        return array_values($themes);
    }

    public function get(string $slug): Theme
    {
        if (!isset($this->themes[$slug])) {
            $this->logger->warning('Tema desconocido "{slug}"; se usa el tema por defecto.', ['slug' => $slug]);

            return $this->default();
        }

        return $this->themes[$slug];
    }

    public function default(): Theme
    {
        return $this->defaultTheme;
    }
}
