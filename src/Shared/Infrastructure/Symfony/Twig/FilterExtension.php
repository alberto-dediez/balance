<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Symfony\Twig;

use App\Security\Domain\Model\User\Role;
use Symfony\Component\Translation\TranslatableMessage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FilterExtension extends AbstractExtension
{
    /** @return list<TwigFilter>  */
    public function getFilters(): array
    {
        return [
            new TwigFilter('is_open', $this->isOpen(...)),
            new TwigFilter('is_valued', $this->isValued(...)),
            new TwigFilter('is_stringable', $this->isStringable(...)),
            new TwigFilter('show_field_value', $this->showFieldValue(...)),
            new TwigFilter('show_fields', $this->getShowFields(...)),
        ];
    }

    /** @return list<TwigFunction>  */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_fields', $this->getFields(...)),
        ];
    }

    public function isStringable(mixed $value): bool
    {
        return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
    }

    public function isOpen(object $filter): bool
    {
        $fields = $this->getShowFields($filter);

        return count($fields) > 0;
    }

    public function isValued(mixed $value): bool
    {
        return
            null !== $value && (
                (is_string($value) && '' !== $value)
                || (is_array($value) && count($value) > 0)
                || (!is_string($value) && !is_array($value))
            )
        ;
    }

    /**
     * @return string[]
     */
    public function getFields(object $filter): array
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

    /**
     * @return string[]
     */
    public function getShowFields(object $filter): array
    {
        $ignoreFields = ['limit', 'page', 'orderWay', 'orderBy'];

        $result = [];

        $fields = $this->getFields($filter);

        if ([] === $fields) {
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

    public function showFieldValue(mixed $value): string|TranslatableMessage|int
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

        if ($value instanceof Role) {
            return new TranslatableMessage($value->value, [], 'roles');
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return (string) $value;
    }
}
