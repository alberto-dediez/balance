<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Bulk;

use Symfony\Component\Translation\TranslatableMessage;

trait RemoveAction
{
    public function id(): string
    {
        return 'remove';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Eliminar');
    }
}
