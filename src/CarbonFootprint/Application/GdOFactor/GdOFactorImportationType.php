<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor;

use App\CarbonFootprint\Application\GdOFactor\Import\ImportGdOFactorRequest;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\Type\GdOFactorChoiceType;
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

class GdOFactorImportationType implements ImportationType
{
    public function __construct(
        private readonly GdOFactorAuthorizer $gdOFactorAuthorizer,
    ) {}

    public function code(): string
    {
        return 'gdo_factors';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Factores GdO');
    }

    public function returnRoute(): string
    {
        return 'admin_gdo_factors';
    }

    public function fields(): array
    {
        return [
            'gdOFactor',
            'name',
            'year',
            'co2eq',
            'co2',
            'ch4',
            'n2o',
            'source',
        ];
    }

    public function translationDefinitions(): array
    {
        return [
            'gdOFactor' => new FieldDefinition(fieldType: GdOFactorChoiceType::class),
        ];
    }

    public function assignValueDefinitions(): array
    {
        return [
            'gdOFactor' => new FieldDefinition(fieldType: GdOFactorChoiceType::class),
            'name' => new FieldDefinition(fieldType: TextType::class),
            'year' => new FieldDefinition(fieldType: TextType::class),
            'co2eq' => new FieldDefinition(fieldType: TextType::class),
            'co2' => new FieldDefinition(fieldType: TextType::class),
            'ch4' => new FieldDefinition(fieldType: TextType::class),
            'n2o' => new FieldDefinition(fieldType: TextType::class),
            'source' => new FieldDefinition(fieldType: TextType::class),
        ];
    }

    public function commandBuilder(array $data): Command
    {
        return new ImportGdOFactorRequest(
            gdOFactor: $this->stringOrNull($data['gdOFactor'] ?? null),
            name: $this->stringOrNull($data['name'] ?? null),
            year: $this->stringOrNull($data['year'] ?? null),
            co2eq: $this->stringOrNull($data['co2eq'] ?? null),
            co2: $this->stringOrNull($data['co2'] ?? null),
            ch4: $this->stringOrNull($data['ch4'] ?? null),
            n2o: $this->stringOrNull($data['n2o'] ?? null),
            source: $this->stringOrNull($data['source'] ?? null),
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
                    case 'gdOFactor':
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
        return $this->gdOFactorAuthorizer->canDoThat(
            new GdOFactor(id: GdOFactorId::generate()),
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
