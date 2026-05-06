<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use League\CommonMark\GithubFlavoredMarkdownConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    private GithubFlavoredMarkdownConverter $converter;

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter();
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('md_to_html', $this->mdToHtml(...), ['is_safe' => ['html']]),
        ];
    }

    public function mdToHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
