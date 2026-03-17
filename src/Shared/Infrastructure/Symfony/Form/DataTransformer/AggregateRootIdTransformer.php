<?php

namespace App\Shared\Infrastructure\Symfony\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/** @implements DataTransformerInterface<mixed, mixed> */
readonly class AggregateRootIdTransformer implements DataTransformerInterface
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private string $class
    ) {

    }

    /**
     * @param mixed $value
     * @return string|array<int,string>
     */
    public function transform(mixed $value): string|array
    {
        if (null === $value) {
            return '';
        }

        return is_array($value) ? array_map(fn($id): string => (string) $id, $value) : (string) $value;
    }

    public function reverseTransform($value): mixed
    {
        if (is_array($value) && count($value) === 0) {
            return [];
        }

        if (empty($value)) {
            return null;
        }

        if (!is_array($value)) {
            return new ($this->class)($value);
        }

        return array_map(fn($id): object => new ($this->class)($id), $value);
    }
}
