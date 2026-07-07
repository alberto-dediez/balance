<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material;

use App\CarbonFootprint\Application\Material\Import\ImportMaterialRequest;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MaterialCategoryCodeType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MaterialChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UnitCodeType;
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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Translation\TranslatableMessage;

class MaterialImportationType implements ImportationType
{
    public function __construct(
        private readonly MaterialAuthorizer $materialAuthorizer,
    ) {}

    public function code(): string
    {
        return 'materials';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Materiales');
    }

    public function returnRoute(): string
    {
        return 'admin_materials';
    }

    public function fields(): array
    {
        return [
            (string) new TranslatableMessage('material', [], 'fields'),
            (string) new TranslatableMessage('name', [], 'fields'),
            (string) new TranslatableMessage('category', [], 'fields'),
            (string) new TranslatableMessage('unit', [], 'fields'),
            (string) new TranslatableMessage('year', [], 'fields'),
            (string) new TranslatableMessage('co2eq', [], 'fields'),
            (string) new TranslatableMessage('co2', [], 'fields'),
            (string) new TranslatableMessage('ch4', [], 'fields'),
            (string) new TranslatableMessage('n2o', [], 'fields'),
            (string) new TranslatableMessage('recycledCo2eq', [], 'fields'),
            (string) new TranslatableMessage('recycledCo2', [], 'fields'),
            (string) new TranslatableMessage('recycledCh4', [], 'fields'),
            (string) new TranslatableMessage('recycledN2o', [], 'fields'),
            (string) new TranslatableMessage('source', [], 'fields'),
            (string) new TranslatableMessage('emissionFactorGroup', [], 'fields'),
        ];
    }

    public function translationDefinitions(): array
    {
        return [
            'material' => new FieldDefinition(fieldType: MaterialChoiceType::class),
            'category' => new FieldDefinition(fieldType: MaterialCategoryCodeType::class),
            'unit' => new FieldDefinition(fieldType: UnitCodeType::class),
            'emissionFactorGroup' => new FieldDefinition(fieldType: EmissionFactorGroupChoiceType::class),
        ];
    }

    public function assignValueDefinitions(): array
    {
        return [
            'material' => new FieldDefinition(fieldType: MaterialChoiceType::class),
            'name' => new FieldDefinition(fieldType: TextType::class),
            'category' => new FieldDefinition(fieldType: MaterialCategoryCodeType::class),
            'unit' => new FieldDefinition(fieldType: UnitCodeType::class),
            'year' => new FieldDefinition(fieldType: TextType::class),
            'co2eq' => new FieldDefinition(fieldType: TextType::class),
            'co2' => new FieldDefinition(fieldType: TextType::class),
            'ch4' => new FieldDefinition(fieldType: TextType::class),
            'n2o' => new FieldDefinition(fieldType: TextType::class),
            'recycledCo2eq' => new FieldDefinition(fieldType: TextType::class),
            'recycledCo2' => new FieldDefinition(fieldType: TextType::class),
            'recycledCh4' => new FieldDefinition(fieldType: TextType::class),
            'recycledN2o' => new FieldDefinition(fieldType: TextType::class),
            'source' => new FieldDefinition(fieldType: TextType::class),
            'emissionFactorGroup' => new FieldDefinition(fieldType: EmissionFactorGroupChoiceType::class),
        ];
    }

    public function commandBuilder(array $data): Command
    {
        return new ImportMaterialRequest(
            material: $this->stringOrNull($data['material'] ?? null),
            name: $this->stringOrNull($data['name'] ?? null),
            category: $this->stringOrNull($data['category'] ?? null),
            unit: $this->stringOrNull($data['unit'] ?? null),
            year: $this->stringOrNull($data['year'] ?? null),
            co2eq: $this->stringOrNull($data['co2eq'] ?? null),
            co2: $this->stringOrNull($data['co2'] ?? null),
            ch4: $this->stringOrNull($data['ch4'] ?? null),
            n2o: $this->stringOrNull($data['n2o'] ?? null),
            recycledCo2eq: $this->stringOrNull($data['recycledCo2eq'] ?? null),
            recycledCo2: $this->stringOrNull($data['recycledCo2'] ?? null),
            recycledCh4: $this->stringOrNull($data['recycledCh4'] ?? null),
            recycledN2o: $this->stringOrNull($data['recycledN2o'] ?? null),
            source: $this->stringOrNull($data['source'] ?? null),
            emissionFactorGroup: $this->stringOrNull($data['emissionFactorGroup'] ?? null),
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
                    case 'material':
                    case 'category':
                    case 'unit':
                    case 'emissionFactorGroup':
                        $rowExtractor->addFieldExtractor(
                            new MapExtractor($configuration),
                            $field,
                        );

                        break;
                    default:
                        $rowExtractor->addFieldExtractor(
                            new DirectExtractor($configuration),
                            $field,
                        );

                        break;
                }

                continue;
            }

            $rowExtractor->addFieldExtractor(
                new AssignValueExtractor($defaultValues[$field] ?? null),
                $field,
            );
        }

        return $rowExtractor;
    }

    public function isAuthorized(User $user): bool
    {
        return $this->materialAuthorizer->canDoThat(
            new Material(id: MaterialId::generate()),
            Action::Import,
            $user,
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
