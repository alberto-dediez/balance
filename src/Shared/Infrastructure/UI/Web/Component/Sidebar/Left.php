<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Component\Sidebar;

use App\Shared\Infrastructure\UI\Web\Navigation\Node;
use App\Shared\Infrastructure\UI\Web\Navigation\NodeLister;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Sidebar:Left', template: '@components/Sidebar/Left.html.twig')]
final readonly class Left
{
    public function __construct(
        private NodeLister $nodeLister,
    ) {}

    /**
     * @return list<Node>
     */
    public function nodes(): array
    {
        return $this->nodeLister->nodes();
    }
}
