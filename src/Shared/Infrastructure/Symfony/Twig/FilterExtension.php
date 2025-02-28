<?php

namespace App\Shared\Infrastructure\Symfony\Twig;

use Symfony\Component\Translation\TranslatableMessage;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;

class FilterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('is_open', [$this, 'isOpen']),
            new TwigFilter('is_valued', [$this, 'isValued']),
            new TwigFilter('is_stringable', [$this, 'isStringable']),
            new TwigFilter('show_field_value', [$this, 'showFieldValue']),
            new TwigFilter('show_fields', [$this, 'getShowFields']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_fields', [$this, 'getFields']),
        ];
    }

    public function isStringable($value)
    {
        return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
    }
    
    public function isOpen($filter)
    {
        $fields = $this->getShowFields($filter);

        return count($fields) > 0;
    }

    public function isValued($value)
    {
        return (
            null !== $value && (
                (is_string($value) && strlen($value) > 0)
                || !is_string($value)
            )
        );
    }

    public function getFields($filter)
    {
        $fields = [];

        $reflect = new \ReflectionClass($filter);
        $properties = $reflect->getProperties();

        if (count($properties) > 0) {
            foreach ($properties as $property) {
                $fields[] = $property->name;
            }
        }

        return $fields;
    }

    public function getShowFields($filter)
    {
        $ignoreFields = ['limit', 'page', 'orderWay', 'orderBy'];

        $result = [];

        $fields = $this->getFields($filter);

        if (null == $fields || count($fields) == 0) {
            return $result;
        }
        
        foreach ($fields as $field) {
            if (in_array($field, $ignoreFields)) {
                continue;
            }

            $value = $filter->$field;

            if (!$this->isValued($value)) {
                continue;
            }

            $result[] = $field;
        }

        return $result;
    }

    public function showFieldValue($value)
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? new TranslatableMessage('Sí') : new TranslatableMessage('No');
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        if ($value instanceof \UnitEnum) {
            return $value->value;
        }

        return (string) $value;
    }
}
