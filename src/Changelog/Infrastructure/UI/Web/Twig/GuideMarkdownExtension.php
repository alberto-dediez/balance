<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\Twig;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class GuideMarkdownExtension extends AbstractExtension
{
    private const string TABLE_CLASS = 'table table-striped';
    private const string ALLOWED_SCHEMES_PATTERN = '/^(https?|mailto):/i';

    public function __construct(private readonly UrlGeneratorInterface $urlGenerator) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('guide_article_html', $this->toHtml(...)),
        ];
    }

    public function toHtml(GuideArticle $article): string
    {
        return $this->converter($article)->convert($article->body)->getContent();
    }

    private function converter(GuideArticle $article): MarkdownConverter
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
        $environment->addEventListener(DocumentParsedEvent::class, fn(DocumentParsedEvent $event) => $this->resolveLinks($event, $article));
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

        return new MarkdownConverter($environment);
    }

    private function resolveLinks(DocumentParsedEvent $event, GuideArticle $article): void
    {
        $title = $event->getDocument()->firstChild();

        if ($title instanceof Heading && 1 === $title->getLevel()) {
            $title->detach();
        }

        $links = [];

        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof Link) {
                $links[] = $node;
            }
        }

        foreach ($links as $link) {
            $url = $link->getUrl();

            if (1 === preg_match(self::ALLOWED_SCHEMES_PATTERN, $url)) {
                continue;
            }

            $target = $article->path->resolve($url);

            if (null === $target) {
                $this->unwrap($link);

                continue;
            }

            $link->setUrl($this->urlGenerator->generate('guide_article', ['path' => (string) $target]));
            $link->data->set('attributes/data-assistant-document', '1');
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
