<?php

declare(strict_types=1);

namespace App\Shared\Application\Assistant;

enum ToolMode: string
{
    case Read = 'read';
    case Write = 'write';
}
