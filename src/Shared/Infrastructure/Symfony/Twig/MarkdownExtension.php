<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MarkdownExtension extends AbstractExtension
{
    private GithubFlavoredMarkdownConverter $converter;

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => [],
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);
        $this->converter->getEnvironment()->addExtension(new ExternalLinkExtension());
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
