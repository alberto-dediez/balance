<?php

namespace App\Shared\Infrastructure\Symfony\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class AggregateRootIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private string $class
    ) {

    }

    public function transform($rootId): string|array
    {
        if (null === $rootId) {
            return '';
        }

        return is_array($rootId) ? array_map(function ($id) { return (string) $id; }, $rootId) : (string) $rootId;
    }

    public function reverseTransform($value): mixed
    {
        if (is_array($value) && count($value) === 0) {
            return [];
        }

        if (null === $value || empty($value)) {
            return null;
        }

        if (!is_array($value)) {
            return new ($this->class)($value);
        }

        return array_map(function ($id) { return new ($this->class)($id); }, $value);
    }
}
