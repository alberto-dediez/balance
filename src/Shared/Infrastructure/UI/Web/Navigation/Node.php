<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Navigation;

use Symfony\Component\Translation\TranslatableMessage;

class Node
{
    public string $id;

    /**
     * @param list<Node> $items
     */
    public function __construct(
        public string|TranslatableMessage $label,
        public string $icon,
        public ?string $target = null,
        public array $items = [],
        public bool $selected = false,
    ) {
        $this->id = md5($label . $icon);
    }
}
