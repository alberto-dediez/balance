<?php

namespace App\Shared\Infrastructure\Symfony\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class AggregateRootIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private string $class
    ) {
        
    }

    public function transform($rootId): string
    {
        if (null === $rootId) {
            return '';
        }

        return (string) $rootId;
    }

    public function reverseTransform($value): mixed
    {
        return (null == $value || empty($value)) ? null : new ($this->class)($value);
    }
}