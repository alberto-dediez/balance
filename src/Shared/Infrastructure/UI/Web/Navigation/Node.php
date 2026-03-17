<?php

namespace App\Shared\Infrastructure\UI\Web\Navigation;

class Node
{
    public string $id;

    /**
     * @param list<Node> $items
     */
    public function __construct(
        public string $label,
        public string $icon,
        public ?string $target = null,
        public array $items = [],
        public bool $selected = false
    ) {
        $this->id = md5($label . $icon);
    }
}
