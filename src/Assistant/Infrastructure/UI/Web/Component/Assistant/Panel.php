<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\Component\Assistant;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Shared\Application\Assistant\ToolDocument;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Assistant:Panel', template: '@components/Assistant/Panel.html.twig')]
final readonly class Panel
{
    public function __construct(private AssistantAccess $access, private AssistantSettings $settings) {}

    public function enabled(): bool
    {
        return $this->access->isGranted();
    }

    public function documentMaxBytes(): int
    {
        return $this->settings->documentMaxBytes;
    }

    public function imageMaxBytes(): int
    {
        return ToolDocument::IMAGE_MAX_BYTES;
    }

    public function totalMaxBytes(): int
    {
        return $this->settings->transcriptAttachmentsMaxBytes;
    }
}
