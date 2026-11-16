<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

enum ToolParameterType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Date = 'date';
    case DateTime = 'datetime';
    case Enum = 'enum';
    case Id = 'id';
}
