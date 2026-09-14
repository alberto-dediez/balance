<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Twig;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    private readonly GithubFlavoredMarkdownConverter $converter;

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('changelog_markdown', $this->toHtml(...)),
        ];
    }

    public function toHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
