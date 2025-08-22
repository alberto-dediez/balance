<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Country\Bulk;

use App\Shared\Application\CommandBus\Command;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkAction;
use App\Shared\Infrastructure\UI\Web\Bulk\BulkRequest;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Application\Country\Remove\RemoveCountryRequest;

class RemoveCountry implements BulkAction
{
    public function id(): string
    {
        return 'remove';
    }

    public function name(): string
    {
        return new TranslatableMessage('Eliminar');
    }

    public function commands(BulkRequest $request): array
    {
        if (count($request->elements) == 0) {
            return [];
        }

        $commands = [];

        foreach ($request->elements as $country) {
            if (null === $country || null === $country->id()) {
                continue;
            }

            $commands[] = new RemoveCountryRequest(
                countryId: $country->id()
            );
        }

        return $commands;
    }

    public function success(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("Se ha eliminado el país {id}", ['{id}' => $command->countryId]);
    }

    public function failure(Command $command): TranslatableMessage
    {
        return new TranslatableMessage("No se ha podido eliminar el país {id}", ['{id}' => $command->countryId]);
    }
}