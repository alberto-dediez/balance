<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Importation;

use App\DataExchange\Application\Importation\Execute\Extractor\RowExtractor;
use App\DataExchange\Infrastructure\UI\Web\DTO\FieldDefinition;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Translation\TranslatableMessage;

#[AutoconfigureTag('exchange.importation.type')]
interface ImportationType
{
    public function code(): string;

    public function name(): string|TranslatableMessage;

    public function returnRoute(): string;

    /** @return string[] */
    public function fields(): array;

    /** @return FieldDefinition[] */
    public function translationDefinitions(): array;

    /** @return FieldDefinition[] */
    public function assignValueDefinitions(): array;

    public function buildRowExtractor(Importation $importation): RowExtractor;

    /** @param array<string, mixed> $data */
    public function commandBuilder(array $data): Command;

    public function isAuthorized(User $user): bool;
}
