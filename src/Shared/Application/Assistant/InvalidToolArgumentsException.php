<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

final class InvalidToolArgumentsException extends \DomainException
{
    /** @param list<string> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }

    public static function because(string $error): self
    {
        return new self([$error]);
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
