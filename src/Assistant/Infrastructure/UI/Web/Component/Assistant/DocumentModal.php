<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\Component\Assistant;

use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Assistant:DocumentModal', template: '@components/Assistant/DocumentModal.html.twig')]
final readonly class DocumentModal
{
    public function __construct(private AssistantAccess $access) {}

    public function enabled(): bool
    {
        return $this->access->isGranted();
    }
}
