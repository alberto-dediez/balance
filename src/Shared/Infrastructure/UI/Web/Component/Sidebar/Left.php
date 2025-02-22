<?php

namespace App\Shared\Infrastructure\UI\Web\Component\Sidebar;

use App\Security\Domain\Model\User\Role;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use App\Shared\Infrastructure\UI\Web\Navigation\NodeLister;

#[AsTwigComponent('Sidebar:Left', template: '@components/Sidebar/Left.html.twig')]
final class Left
{
    public function __construct(
        private NodeLister $nodeLister
    ) {

    }

    public function nodes()
    {
        return $this->nodeLister->nodes();
    }
}