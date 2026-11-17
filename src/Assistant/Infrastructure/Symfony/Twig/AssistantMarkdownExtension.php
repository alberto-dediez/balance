<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Symfony\Twig;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Image;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
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
    private const string REFERENCE_LINK_PATTERN = '/\[([^\]]*)\]\((route:[^)\s]+)\)/';
    private const string ALLOWED_SCHEMES_PATTERN = '/^(https?|mailto):/i';

    private readonly MarkdownConverter $converter;

    public function __construct(private readonly AssistantLinkResolver $links)
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
        $environment->addEventListener(DocumentParsedEvent::class, $this->resolveLinks(...));
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
            new TwigFilter('assistant_copy_text', $this->toCopyText(...)),
        ];
    }

    public function toHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }

    public function toCopyText(string $markdown): string
    {
        return (string) preg_replace_callback(
            self::REFERENCE_LINK_PATTERN,
            function (array $matches): string {
                $url = $this->links->resolve($matches[2], true);

                return null === $url ? $matches[1] : sprintf('[%s](%s)', $matches[1], $url);
            },
            $markdown,
        );
    }

    private function resolveLinks(DocumentParsedEvent $event): void
    {
        $links = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Link) {
                $links[] = $node;
            }
        }

        foreach ($links as $link) {
            $url = $link->getUrl();

            if ($this->links->isReference($url)) {
                $resolved = $this->links->resolve($url);

                if (null !== $resolved) {
                    $link->setUrl($resolved);

                    if ($this->links->isDocument($url)) {
                        $link->data->set('attributes/data-assistant-document', '1');
                    }

                    continue;
                }

                $this->unwrap($link);

                continue;
            }

            if (1 !== preg_match(self::ALLOWED_SCHEMES_PATTERN, $url) || $this->links->isInternal($url)) {
                $this->unwrap($link);
            }
        }
    }

    private function unwrap(Link $link): void
    {
        foreach ($link->children() as $child) {
            $link->insertBefore($child);
        }

        $link->detach();
    }
}
