<?php

namespace App\Shared\Infrastructure\UI\Web\Bulk;

use Symfony\Component\Translation\TranslatableMessage;

trait RemoveAction
{
    public function id(): string
    {
        return 'remove';
    }

    public function name(): string
    {
        return new TranslatableMessage('Eliminar');
    }
}