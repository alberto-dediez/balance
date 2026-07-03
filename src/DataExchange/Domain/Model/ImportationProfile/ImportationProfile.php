<?php

declare(strict_types=1);

namespace App\DataExchange\Domain\Model\ImportationProfile;

use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Model\AggregateRoot;

class ImportationProfile
{
    use AggregateRoot;

    /**
     * @param array<string, string>                $fieldsByColumn
     * @param array<string, array<string, string>> $translationsByColumn
     * @param array<string, string>                $defaultValues
     */
    public function __construct(
        protected ImportationProfileId $id,
        protected ?AccountId $accountId = null,
        protected string $type = '',
        protected array $fieldsByColumn = [],
        protected array $translationsByColumn = [],
        protected array $defaultValues = [],
        protected \DateTimeImmutable $updatedAt = new \DateTimeImmutable('NOW'),
    ) {}

    public function id(): ImportationProfileId
    {
        return $this->id;
    }

    public function accountId(): ?AccountId
    {
        return $this->accountId;
    }

    public function type(): string
    {
        return $this->type;
    }

    /** @return array<string, string> */
    public function fieldsByColumn(): array
    {
        return $this->fieldsByColumn;
    }

    /** @return array<string, array<string, string>> */
    public function translationsByColumn(): array
    {
        return $this->translationsByColumn;
    }

    /** @return array<string, string> */
    public function defaultValues(): array
    {
        return $this->defaultValues;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function register(?AccountId $accountId, string $type): void
    {
        $this->recordApplyAndPublish(
            new ImportationProfileRegistered(
                importationProfileId: $this->id(),
                accountId: $accountId,
                type: $type,
            ),
        );
    }

    /**
     * @param array<string, string>                $fieldsByColumn
     * @param array<string, array<string, string>> $translationsByColumn
     * @param array<string, string>                $defaultValues
     */
    public function memorize(array $fieldsByColumn, array $translationsByColumn, array $defaultValues): void
    {
        $this->recordApplyAndPublish(
            new ImportationProfileChoicesMemorized(
                importationProfileId: $this->id(),
                fieldsByColumn: $fieldsByColumn,
                translationsByColumn: $translationsByColumn,
                defaultValues: $defaultValues,
            ),
        );
    }

    /**
     * @param ImportationColumnConfiguration[] $configurations
     *
     * @return ImportationColumnConfiguration[]
     */
    public function applyToConfigurations(array $configurations): array
    {
        return array_map(
            fn(ImportationColumnConfiguration $configuration) => new ImportationColumnConfiguration(
                column: $configuration->column,
                name: $configuration->name,
                field: $configuration->field ?? ($this->fieldsByColumn[$configuration->name] ?? null),
                values: $configuration->values,
                translations: array_map(
                    fn(ColumnValueTranslation $translation) => new ColumnValueTranslation(
                        value: $translation->value,
                        translation: $translation->translation ?? ($this->translationsByColumn[$configuration->name][$translation->value] ?? null),
                    ),
                    $configuration->translations,
                ),
            ),
            $configurations,
        );
    }

    public function applyImportationProfileRegistered(ImportationProfileRegistered $event): void
    {
        $this->accountId = $event->accountId();
        $this->type = $event->type();
        $this->updatedAt = $event->occurredOn();
    }

    public function applyImportationProfileChoicesMemorized(ImportationProfileChoicesMemorized $event): void
    {
        $this->fieldsByColumn = array_replace($this->fieldsByColumn, $event->fieldsByColumn());

        foreach ($event->translationsByColumn() as $column => $translations) {
            $this->translationsByColumn[$column] = array_replace($this->translationsByColumn[$column] ?? [], $translations);
        }

        $this->defaultValues = array_replace($this->defaultValues, $event->defaultValues());
        $this->updatedAt = $event->occurredOn();
    }
}
