<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use League\CommonMark\Extension\CommonMark\Node\Block\HtmlBlock;
use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
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
            'renderer' => [
                'soft_break' => "<br>\n",
            ],
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => [],
                'open_in_new_window' => true,
                'noopener' => 'external',
                'noreferrer' => 'external',
            ],
        ]);
        $environment = $this->converter->getEnvironment();
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addRenderer(HtmlInline::class, new AllowedHtmlTagsRenderer(), 100);
        $environment->addRenderer(HtmlBlock::class, new AllowedHtmlTagsRenderer(), 100);
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('md_to_html', $this->mdToHtml(...), ['is_safe' => ['html']]),
        ];
    }

    public function mdToHtml(string $markdown): string
    {
        $markdown = (string) preg_replace('#^[ \t]*<br\s*/?>[ \t]*$#mi', "$0\n", $markdown);

        return $this->converter->convert($markdown)->getContent();
    }
}
