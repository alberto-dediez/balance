<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\Exportation;

use App\Security\Domain\Model\User\User;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Translation\TranslatableMessage;

#[AutoconfigureTag('exchange.exportation.type')]
interface ExportationType
{
    public function code(): string;

    public function name(): string|TranslatableMessage;

    public function returnRoute(): string;

    /** @return ExportColumnDefinition[] */
    public function columnDefinitions(): array;

    /** @return class-string<\Symfony\Component\Form\FormTypeInterface<mixed>> */
    public function filterFormClass(): string;

    public function createFilter(): object;

    /**
     * Serializes the filter DTO to a scalar-only array so it can be persisted and
     * rebuilt later by the asynchronous execution job.
     *
     * @return array<string, mixed>
     */
    public function dehydrateFilter(object $filter): array;

    /** @param array<string, mixed> $criteria */
    public function hydrateFilter(array $criteria): object;

    /**
     * Yields the already filtered rows. Each row is an associative array keyed by
     * column key. The implementation queries its own repository and applies the
     * per-column formatting (raw vs readable).
     *
     * @param string[] $selectedColumnKeys
     *
     * @return iterable<array<string, scalar|null>>
     */
    public function rows(object $filter, array $selectedColumnKeys): iterable;

    public function isAuthorized(User $user): bool;
}
