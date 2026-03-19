<?php

declare(strict_types=1);

namespace App\Waste\Application\Measurement;

use App\DataExchange\Application\Importation\Execute\Extractor\AssignValueExtractor;
use App\DataExchange\Application\Importation\Execute\Extractor\DirectExtractor;
use App\DataExchange\Application\Importation\Execute\Extractor\MapExtractor;
use App\DataExchange\Application\Importation\Execute\Extractor\RowExtractor;
use App\DataExchange\Domain\Model\Importation\Importation;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\Importation\ImportationType;
use App\DataExchange\Infrastructure\UI\Web\DTO\FieldDefinition;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Waste\Application\Measurement\Import\ImportMeasurementRequest;
use App\Waste\Domain\Model\Measurement\Measurement;
use App\Waste\Domain\Model\Measurement\MeasurementId;
use App\Waste\Domain\Model\Measurement\Unit;
use App\Waste\Infrastructure\Symfony\Form\Type\ContainerIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\PurposeValueType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteIdType;
use App\Waste\Infrastructure\Symfony\Form\Type\WasteManagerIdType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class MeasurementImportationType implements ImportationType
{
    private readonly array $fields;

    public function __construct(
        private readonly MeasurementAuthorizer $measurementAuthorizer,
        TranslatorInterface $translator,
    ) {
        $unitChoicesForImportation = array_filter(Unit::cases(), fn(Unit $unit): bool => Unit::Percentage !== $unit);

        $this->fields = [
            'containerId' => [
                'translationDefinition' => new FieldDefinition(
                    fieldType: ContainerIdType::class,
                ),
                'assignValueDefinition' => new FieldDefinition(
                    fieldType: ContainerIdType::class,
                ),
            ],
            'propertyId' =>  [
                'translationDefinition' => new FieldDefinition(
                    fieldType: PropertyIdType::class,
                ),
                'assignValueDefinition' =>  new FieldDefinition(
                    fieldType: PropertyIdType::class,
                ),
            ],
            'wasteId' => [
                'translationDefinition' => new FieldDefinition(
                    fieldType: WasteIdType::class,
                ),
                'assignValueDefinition' => new FieldDefinition(
                    fieldType: WasteIdType::class,
                ),
            ],
            'purpose' => [
                'translationDefinition' => new FieldDefinition(
                    fieldType: PurposeValueType::class,
                ),
                'assignValueDefinition' =>  new FieldDefinition(
                    fieldType: PurposeValueType::class,
                ),
            ],
            'wasteManagerId' => [
                'translationDefinition' => new FieldDefinition(
                    fieldType: WasteManagerIdType::class,
                ),
                'assignValueDefinition' =>  new FieldDefinition(
                    fieldType: WasteManagerIdType::class,
                ),
            ],
            'quantity' => [
                'translationDefinition' => null,
                'assignValueDefinition' => null,
            ],
            'load' => [
                'translationDefinition' => null,
                'assignValueDefinition' => null,
            ],
            'justification' => [
                'translationDefinition' => null,
                'assignValueDefinition' => new FieldDefinition(
                    fieldType: TextType::class,
                ),
            ],
            'unit' => [
                'translationDefinition' => new FieldDefinition(
                    fieldType: ChoiceType::class,
                    options: [
                        'choices' => array_combine(
                            array_map(fn(Unit $unit): string => $unit->label($translator), $unitChoicesForImportation),
                            array_map(fn(Unit $unit) => $unit->value, $unitChoicesForImportation),
                        ),
                    ],
                ),
                'assignValueDefinition' => null,
            ],
            'day' => [
                'translationDefinition' => null,
                'assignValueDefinition' => new FieldDefinition(
                    fieldType: DateTimeType::class,
                ),
            ],
            'reference' => [
                'translationDefinition' => null,
                'assignValueDefinition' => new FieldDefinition(
                    fieldType: TextType::class,
                ),
            ],
        ];
    }

    public function code(): string
    {
        return 'measurements';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Mediciones');
    }

    public function returnRoute(): string
    {
        return 'measurements';
    }

    public function fields(): array
    {
        return array_keys($this->fields);
    }

    public function translationDefinitions(): array
    {
        return array_map(fn(array $definition) => $definition['translationDefinition'] ?? null, $this->fields);
    }

    public function assignValueDefinitions(): array
    {
        return array_map(fn(array $definition) => $definition['assignValueDefinition'] ?? null, $this->fields);
    }

    public function commandBuilder(array $data): Command
    {
        return new ImportMeasurementRequest(
            containerId: $data['containerId'] ?? null,
            propertyId: $data['propertyId'] ?? null,
            wasteId: $data['wasteId'] ?? null,
            purpose: $data['purpose'] ?? null,
            wasteManagerId: $data['wasteManagerId'] ?? null,
            quantity: $data['quantity'] ?? null,
            load: $data['load'] ?? null,
            justification: $data['justification'] ?? null,
            unit: $data['unit'] ?? null,
            day: $data['day'] ?? null,
            reference: $data['reference'] ?? null,
        );
    }

    public function buildRowExtractor(Importation $importation): RowExtractor
    {
        $rowExtractor = new RowExtractor();

        $defaultValues = $importation->defaultValues();

        foreach ($this->fields() as $field) {
            $configuration = $importation->configurationForField($field);

            if ($configuration instanceof ImportationColumnConfiguration) {
                switch ($field) {
                    case 'containerId':
                    case 'propertyId':
                    case 'wasteId':
                    case 'purpose':
                    case 'wasteManagerId':
                    case 'unit':
                        $rowExtractor->addFieldExtractor(
                            new MapExtractor($configuration),
                            $field,
                        );
                        break;
                    case 'quantity':
                    case 'load':
                    case 'justification':
                    case 'day':
                    case 'reference':
                        $rowExtractor->addFieldExtractor(
                            new DirectExtractor($configuration),
                            $field,
                        );
                        break;
                }

                continue;
            }

            $defaultValue = $defaultValues[$field] ?? null;

            $rowExtractor->addFieldExtractor(
                new AssignValueExtractor($defaultValue),
                $field,
            );
        }

        return $rowExtractor;
    }

    public function isAuthorized(User $user): bool
    {
        return $this->measurementAuthorizer->canDoThat(
            new Measurement(
                id: MeasurementId::generate(),
                accountId: $user->accountId(),
            ),
            Action::Import,
            $user,
        );
    }
}
