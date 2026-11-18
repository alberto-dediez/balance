<?php

declare(strict_types=1);

namespace App\Assistant\Application\Engine;

use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolParameterType;

class ToolSchemaBuilder
{
    /** @return array<string, mixed> */
    public function schemaOf(AssistantTool $tool): array
    {
        $properties = [];
        $required = [];

        foreach ($tool->parameters() as $parameter) {
            $single = $this->single($parameter);

            $properties[$parameter->name] = $parameter->multiple
                ? ['type' => 'array', 'description' => $single['description'], 'items' => array_diff_key($single, ['description' => true])]
                : $single;

            if ($parameter->required) {
                $required[] = $parameter->name;
            }
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false,
        ];
    }

    /** @return array<string, mixed> */
    private function single(ToolParameter $parameter): array
    {
        $schema = match ($parameter->type) {
            ToolParameterType::String, ToolParameterType::Text => ['type' => 'string'],
            ToolParameterType::Integer => ['type' => 'integer'],
            ToolParameterType::Decimal => ['type' => 'number'],
            ToolParameterType::Boolean => ['type' => 'boolean'],
            ToolParameterType::Date, ToolParameterType::DateTime, ToolParameterType::Id => ['type' => 'string'],
            ToolParameterType::Enum => ['type' => 'string', 'enum' => $parameter->enumValues],
        };

        $schema['description'] = trim($parameter->description . ' ' . match ($parameter->type) {
            ToolParameterType::Id => 'UUID.',
            ToolParameterType::Date => 'Formato AAAA-MM-DD.',
            ToolParameterType::DateTime => 'Formato AAAA-MM-DD HH:MM, en la zona horaria del usuario.',
            default => '',
        });

        if (null !== $parameter->maxLength) {
            $schema['maxLength'] = $parameter->maxLength;
        }

        if (null !== $parameter->min) {
            $schema['minimum'] = $parameter->min;
        }

        if (null !== $parameter->max) {
            $schema['maximum'] = $parameter->max;
        }

        return $schema;
    }
}
