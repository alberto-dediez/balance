<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\ElectricityMix;

use App\CarbonFootprint\Application\ElectricityMix\Import\ImportElectricityMixRequest;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMix;
use App\CarbonFootprint\Domain\Model\ElectricityMix\ElectricityMixId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\ElectricityMixChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
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

class ElectricityMixImportationType implements ImportationType
{
    public function __construct(
        private readonly ElectricityMixAuthorizer $electricityMixAuthorizer,
    ) {}

    public function code(): string
    {
        return 'electricity_mixes';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Mixes eléctricos');
    }

    public function returnRoute(): string
    {
        return 'admin_electricity_mixes';
    }

    public function fields(): array
    {
        return [
            (string) new TranslatableMessage('electricityMix', [], 'fields'),
            (string) new TranslatableMessage('country', [], 'fields'),
            (string) new TranslatableMessage('year', [], 'fields'),
            (string) new TranslatableMessage('co2eq', [], 'fields'),
            (string) new TranslatableMessage('co2', [], 'fields'),
            (string) new TranslatableMessage('ch4', [], 'fields'),
            (string) new TranslatableMessage('n2o', [], 'fields'),
            (string) new TranslatableMessage('source', [], 'fields'),
            (string) new TranslatableMessage('emissionFactorGroup', [], 'fields'),
        ];
    }

    public function translationDefinitions(): array
    {
        return [
            'electricityMix' => new FieldDefinition(fieldType: ElectricityMixChoiceType::class),
            'emissionFactorGroup' => new FieldDefinition(fieldType: EmissionFactorGroupChoiceType::class),
        ];
    }

    public function assignValueDefinitions(): array
    {
        return [
            'electricityMix' => new FieldDefinition(fieldType: ElectricityMixChoiceType::class),
            'country' => new FieldDefinition(fieldType: TextType::class),
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
        return new ImportElectricityMixRequest(
            electricityMix: $this->stringOrNull($data['electricityMix'] ?? null),
            country: $this->stringOrNull($data['country'] ?? null),
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
                    case 'electricityMix':
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
        return $this->electricityMixAuthorizer->canDoThat(
            new ElectricityMix(id: ElectricityMixId::generate()),
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
