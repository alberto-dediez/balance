<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

final class DuplicatedToolNameException extends \LogicException
{
    public function __construct(string $name, string $class, string $otherClass)
    {
        parent::__construct(sprintf('The assistant tool "%s" is declared by %s and %s', $name, $class, $otherClass));
    }
}
