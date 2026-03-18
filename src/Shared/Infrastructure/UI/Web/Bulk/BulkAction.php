<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\UI\Web\Bulk;

use App\Shared\Application\CommandBus\Command;
use Symfony\Component\Translation\TranslatableMessage;

interface BulkAction
{
    public function id(): string;

    public function name(): string|TranslatableMessage;

    /** @return list<Command>  */
    public function commands(BulkRequest $request): array;

    public function success(Command $command): TranslatableMessage;

    public function failure(Command $command): TranslatableMessage;
}
