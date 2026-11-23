<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use League\CommonMark\Node\Node;
use League\CommonMark\Node\RawMarkupContainerInterface;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

final class AllowedHtmlTagsRenderer implements NodeRendererInterface
{
    private const string ALLOWED_TAG = '#&lt;(/?)(br|strong|b|em|i|u|s|del|strike|sub|sup)\s*/?&gt;#i';

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        if (!$node instanceof RawMarkupContainerInterface) {
            return '';
        }

        $escaped = htmlspecialchars($node->getLiteral(), \ENT_NOQUOTES | \ENT_HTML5, 'UTF-8');

        return (string) preg_replace_callback(
            self::ALLOWED_TAG,
            static fn(array $tag): string => 'br' === strtolower($tag[2]) ? '<br>' : '<' . $tag[1] . strtolower($tag[2]) . '>',
            $escaped,
        );
    }
}
