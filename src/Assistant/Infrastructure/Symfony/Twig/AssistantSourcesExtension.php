<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\Symfony\Twig;

use App\Assistant\Application\Engine\Toolbox;
use App\Assistant\Domain\Model\Conversation\Message;
use App\Shared\Application\Assistant\SourceAssistantTool;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssistantSourcesExtension extends AbstractExtension
{
    public function __construct(
        private readonly Toolbox $toolbox,
        private readonly AssistantLinkResolver $links,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('assistant_sources', $this->sources(...)),
        ];
    }

    /**
     * @return list<array{label: string, url: string, document: bool}>
     */
    public function sources(Message $message): array
    {
        $sources = [];

        foreach ($message->toolCalls() as $call) {
            if ($call->failed()) {
                continue;
            }

            $tool = $this->toolbox->get($call->tool());

            if (!$tool instanceof SourceAssistantTool) {
                continue;
            }

            $href = $tool->sourceHref($call->argumentValues());
            $url = null === $href ? null : $this->links->resolve($href);

            if (null === $href || null === $url || isset($sources[$url])) {
                continue;
            }

            $sources[$url] = [
                'label' => $call->summary(),
                'url' => $url,
                'document' => $this->links->isDocument($href),
            ];
        }

        return array_values($sources);
    }
}
