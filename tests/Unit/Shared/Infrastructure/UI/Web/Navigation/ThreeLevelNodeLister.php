<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\UI\Web\Navigation;

use App\Shared\Infrastructure\UI\Web\Navigation\Node;
use App\Shared\Infrastructure\UI\Web\Navigation\NodeLister;

final class ThreeLevelNodeLister extends NodeLister
{
    protected function roots(): array
    {
        return [
            new Node(
                label: 'Raíz',
                icon: 'solar:shield-user-line-duotone',
                items: [
                    new Node(label: 'Suelta', icon: 'solar:folder-with-files-line-duotone', target: '/es/x'),
                    new Node(
                        label: 'Grupo A',
                        target: '/es/grupo-a',
                        items: [
                            new Node(label: 'A1', icon: 'solar:upload-square-line-duotone', target: '/es/x/export'),
                            new Node(label: 'A2', icon: 'solar:users-group-rounded-line-duotone', target: '/es/a2'),
                        ],
                    ),
                    new Node(
                        label: 'Grupo B',
                        items: [
                            new Node(label: 'B1', icon: 'solar:record-minimalistic-line-duotone', target: '/es/b1'),
                        ],
                    ),
                ],
            ),
            new Node(label: 'Inicio', icon: 'solar:home-line-duotone', target: '/es/'),
        ];
    }
}
