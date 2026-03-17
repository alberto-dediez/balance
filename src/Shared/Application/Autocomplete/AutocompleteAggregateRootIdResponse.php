<?php

namespace App\Shared\Application\Autocomplete;

trait AutocompleteAggregateRootIdResponse
{
    /**
     * @var array<int, mixed> $elements
     */
    public array $elements = [];

    /**
     * @param array<int, mixed> $elements
     */
    public function __construct(array $elements)
    {
        if (count($elements) === 0) {
            return ;
        }

        foreach ($elements as $element) {
            if (false === $element instanceof \Stringable) {
                throw new \RuntimeException("No Stringable passed");
            }

            if (false === method_exists($element, 'id')) {
                throw new \RuntimeException("No id() method passed");
            }

            $this->elements[] = new AutocompleteElement(
                label: (string) $element,
                value: (string) $element->id()
            );
        }
    }
}
