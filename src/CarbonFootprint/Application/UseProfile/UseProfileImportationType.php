<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile;

use App\CarbonFootprint\Application\UseProfile\Import\ImportUseProfileRequest;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\EmissionFactorGroupChoiceType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UnitCodeType;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\UseProfileChoiceType;
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

class UseProfileImportationType implements ImportationType
{
    public function __construct(
        private readonly UseProfileAuthorizer $useProfileAuthorizer,
    ) {}

    public function code(): string
    {
        return 'use_profiles';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Perfiles de uso');
    }

    public function returnRoute(): string
    {
        return 'admin_use_profiles';
    }

    public function fields(): array
    {
        return [
            (string) new TranslatableMessage('useProfile', [], 'fields'),
            (string) new TranslatableMessage('name', [], 'fields'),
            (string) new TranslatableMessage('unit', [], 'fields'),
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
            'useProfile' => new FieldDefinition(fieldType: UseProfileChoiceType::class),
            'unit' => new FieldDefinition(fieldType: UnitCodeType::class),
            'emissionFactorGroup' => new FieldDefinition(fieldType: EmissionFactorGroupChoiceType::class),
        ];
    }

    public function assignValueDefinitions(): array
    {
        return [
            'useProfile' => new FieldDefinition(fieldType: UseProfileChoiceType::class),
            'name' => new FieldDefinition(fieldType: TextType::class),
            'unit' => new FieldDefinition(fieldType: UnitCodeType::class),
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
        return new ImportUseProfileRequest(
            useProfile: $this->stringOrNull($data['useProfile'] ?? null),
            name: $this->stringOrNull($data['name'] ?? null),
            unit: $this->stringOrNull($data['unit'] ?? null),
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
                    case 'useProfile':
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
        return $this->useProfileAuthorizer->canDoThat(
            new UseProfile(id: UseProfileId::generate()),
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
