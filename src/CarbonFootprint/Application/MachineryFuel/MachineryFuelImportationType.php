<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MachineryFuel;

use App\CarbonFootprint\Application\MachineryFuel\Import\ImportMachineryFuelRequest;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuel;
use App\CarbonFootprint\Domain\Model\MachineryFuel\MachineryFuelId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MachineryFuelChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\MachineryFuelMachineryTypeType;
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

class MachineryFuelImportationType implements ImportationType
{
    public function __construct(
        private readonly MachineryFuelAuthorizer $machineryFuelAuthorizer,
    ) {}

    public function code(): string
    {
        return 'machinery_fuels';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Combustible de maquinaria');
    }

    public function returnRoute(): string
    {
        return 'admin_machinery_fuels';
    }

    public function fields(): array
    {
        return [
            'machinery_fuel',
            'name',
            'unit',
            'machinery_type',
            'year',
            'co2eq',
            'co2',
            'ch4',
            'n2o',
            'source',
            'emissionFactorGroup',
        ];
    }

    public function translationDefinitions(): array
    {
        return [
            'machinery_fuel' => new FieldDefinition(fieldType: MachineryFuelChoiceType::class),
            'unit' => new FieldDefinition(fieldType: UnitCodeType::class),
            'machinery_type' => new FieldDefinition(fieldType: MachineryFuelMachineryTypeType::class),
            'emissionFactorGroup' => new FieldDefinition(fieldType: EmissionFactorGroupChoiceType::class),
        ];
    }

    public function assignValueDefinitions(): array
    {
        return [
            'machinery_fuel' => new FieldDefinition(fieldType: MachineryFuelChoiceType::class),
            'name' => new FieldDefinition(fieldType: TextType::class),
            'unit' => new FieldDefinition(fieldType: UnitCodeType::class),
            'machinery_type' => new FieldDefinition(fieldType: MachineryFuelMachineryTypeType::class),
            'year' => new FieldDefinition(fieldType: TextType::class),
            'co2eq' => new FieldDefinition(fieldType: TextType::class),
            'co2' => new FieldDefinition(fieldType: TextType::class),
            'ch4' => new FieldDefinition(fieldType: TextType::class),
            'n2o' => new FieldDefinition(fieldType: TextType::class),
            'source' => new FieldDefinition(fieldType: TextType::class),
            'emissionFactorGroup' => new FieldDefinition(fieldType: EmissionFactorGroupChoiceType::class),
        ];
    }

    public function commandBuilder(array $data): Command
    {
        return new ImportMachineryFuelRequest(
            machineryFuel: $this->stringOrNull($data['machinery_fuel'] ?? null),
            name: $this->stringOrNull($data['name'] ?? null),
            unit: $this->stringOrNull($data['unit'] ?? null),
            machineryType: $this->stringOrNull($data['machinery_type'] ?? null),
            year: $this->stringOrNull($data['year'] ?? null),
            co2eq: $this->stringOrNull($data['co2eq'] ?? null),
            co2: $this->stringOrNull($data['co2'] ?? null),
            ch4: $this->stringOrNull($data['ch4'] ?? null),
            n2o: $this->stringOrNull($data['n2o'] ?? null),
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
                    case 'machinery_fuel':
                    case 'unit':
                    case 'machinery_type':
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
        return $this->machineryFuelAuthorizer->canDoThat(
            new MachineryFuel(id: MachineryFuelId::generate()),
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
