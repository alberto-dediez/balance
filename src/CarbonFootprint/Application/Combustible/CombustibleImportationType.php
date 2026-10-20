<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible;

use App\CarbonFootprint\Application\Combustible\Import\ImportCombustibleRequest;
use App\CarbonFootprint\Domain\Model\Combustible\Combustible;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\CombustibleChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
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

class CombustibleImportationType implements ImportationType
{
    public function __construct(
        private readonly CombustibleAuthorizer $combustibleAuthorizer,
    ) {}

    public function code(): string
    {
        return 'combustibles';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Combustibles');
    }

    public function returnRoute(): string
    {
        return 'admin_combustibles';
    }

    public function fields(): array
    {
        return [
            (string) new TranslatableMessage('combustible', [], 'fields'),
            (string) new TranslatableMessage('name', [], 'fields'),
            (string) new TranslatableMessage('unit', [], 'fields'),
            (string) new TranslatableMessage('year', [], 'fields'),
            (string) new TranslatableMessage('co2eq', [], 'fields'),
            (string) new TranslatableMessage('co2', [], 'fields'),
            (string) new TranslatableMessage('ch4', [], 'fields'),
            (string) new TranslatableMessage('n2o', [], 'fields'),
            (string) new TranslatableMessage('source', [], 'fields'),
            (string) new TranslatableMessage('wellToTankCo2eq', [], 'fields'),
            (string) new TranslatableMessage('wellToTankCo2', [], 'fields'),
            (string) new TranslatableMessage('wellToTankCh4', [], 'fields'),
            (string) new TranslatableMessage('wellToTankN2o', [], 'fields'),
            (string) new TranslatableMessage('wellToTankSource', [], 'fields'),
            (string) new TranslatableMessage('emissionFactorGroup', [], 'fields'),
        ];
    }

    public function translationDefinitions(): array
    {
        return [
            'combustible' => new FieldDefinition(fieldType: CombustibleChoiceType::class),
            'unit' => new FieldDefinition(fieldType: UnitCodeType::class),
            'emissionFactorGroup' => new FieldDefinition(fieldType: EmissionFactorGroupChoiceType::class),
        ];
    }

    public function assignValueDefinitions(): array
    {
        return [
            'combustible' => new FieldDefinition(fieldType: CombustibleChoiceType::class),
            'name' => new FieldDefinition(fieldType: TextType::class),
            'unit' => new FieldDefinition(fieldType: UnitCodeType::class),
            'year' => new FieldDefinition(fieldType: TextType::class),
            'co2eq' => new FieldDefinition(fieldType: TextType::class),
            'co2' => new FieldDefinition(fieldType: TextType::class),
            'ch4' => new FieldDefinition(fieldType: TextType::class),
            'n2o' => new FieldDefinition(fieldType: TextType::class),
            'source' => new FieldDefinition(fieldType: TextType::class),
            'wellToTankCo2eq' => new FieldDefinition(fieldType: TextType::class),
            'wellToTankCo2' => new FieldDefinition(fieldType: TextType::class),
            'wellToTankCh4' => new FieldDefinition(fieldType: TextType::class),
            'wellToTankN2o' => new FieldDefinition(fieldType: TextType::class),
            'wellToTankSource' => new FieldDefinition(fieldType: TextType::class),
            'emissionFactorGroup' => new FieldDefinition(fieldType: EmissionFactorGroupChoiceType::class),
        ];
    }

    public function commandBuilder(array $data): Command
    {
        return new ImportCombustibleRequest(
            combustible: $this->stringOrNull($data['combustible'] ?? null),
            name: $this->stringOrNull($data['name'] ?? null),
            unit: $this->stringOrNull($data['unit'] ?? null),
            year: $this->stringOrNull($data['year'] ?? null),
            co2eq: $this->stringOrNull($data['co2eq'] ?? null),
            co2: $this->stringOrNull($data['co2'] ?? null),
            ch4: $this->stringOrNull($data['ch4'] ?? null),
            n2o: $this->stringOrNull($data['n2o'] ?? null),
            source: $this->stringOrNull($data['source'] ?? null),
            wellToTankCo2eq: $this->stringOrNull($data['wellToTankCo2eq'] ?? null),
            wellToTankCo2: $this->stringOrNull($data['wellToTankCo2'] ?? null),
            wellToTankCh4: $this->stringOrNull($data['wellToTankCh4'] ?? null),
            wellToTankN2o: $this->stringOrNull($data['wellToTankN2o'] ?? null),
            wellToTankSource: $this->stringOrNull($data['wellToTankSource'] ?? null),
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
                    case 'combustible':
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
        return $this->combustibleAuthorizer->canDoThat(
            new Combustible(id: CombustibleId::generate()),
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
