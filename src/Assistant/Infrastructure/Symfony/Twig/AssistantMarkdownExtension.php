<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Symfony\Twig;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkProcessor;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\Xml;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AssistantMarkdownExtension extends AbstractExtension
{
    private const string TABLE_CLASS = 'table table-striped';

    private readonly MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'default_attributes' => [
                'attributes' => [
                    Table::class => ['class' => self::TABLE_CLASS],
                ],
            ],
            'external_link' => [
                'internal_hosts' => [],
                'open_in_new_window' => true,
                'nofollow' => ExternalLinkProcessor::APPLY_EXTERNAL,
                'noopener' => ExternalLinkProcessor::APPLY_EXTERNAL,
                'noreferrer' => ExternalLinkProcessor::APPLY_EXTERNAL,
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new DefaultAttributesExtension());
        $environment->addExtension(new ExternalLinkExtension());
        $environment->addRenderer(Image::class, new class implements NodeRendererInterface {
            public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
            {
                if (!$node instanceof Image) {
                    return '';
                }

                $alt = $childRenderer->renderNodes($node->children());

                return '' === trim($alt) ? Xml::escape($node->getUrl()) : $alt;
            }
        }, 10);

        $this->converter = new MarkdownConverter($environment);
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('assistant_markdown', $this->toHtml(...)),
        ];
    }

    public function toHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
