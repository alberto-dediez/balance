<?php

declare(strict_types=1);

namespace App\Shared\Application\Shortcut;

class DuplicatedShortcutException extends \LogicException
{
    public function __construct(KeyCombination $combination, string $label, string $otherLabel)
    {
        parent::__construct(sprintf('The shortcut %s is declared by "%s" and "%s"', $combination->label(), $label, $otherLabel));
    }
}
