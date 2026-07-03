<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataExchange\Domain\Model\ImportationProfile;

use App\DataExchange\Domain\Model\Importation\ColumnValueTranslation;
use App\DataExchange\Domain\Model\Importation\ImportationColumnConfiguration;
use App\DataExchange\Domain\Model\ImportationProfile\ImportationProfileId;
use App\Security\Domain\Model\Account\AccountId;
use PHPUnit\Framework\TestCase;

class ImportationProfileTest extends TestCase
{
    public function testMemorizeAccumulatesChoicesAcrossCalls(): void
    {
        $profile = ImportationProfileMother::fromTypeAndAccount('measurements', AccountId::generate());

        $profile->memorize(
            fieldsByColumn: ['nombre' => 'name', 'edad' => 'age'],
            translationsByColumn: ['clase' => ['Clase Alpha' => 'alpha']],
            defaultValues: ['unit' => 'kg'],
        );

        $profile->memorize(
            fieldsByColumn: ['edad' => 'yearsOld', 'superpoder' => 'power'],
            translationsByColumn: ['clase' => ['Clase Beta' => 'beta']],
            defaultValues: ['status' => 'active'],
        );

        $this->assertEquals(
            ['nombre' => 'name', 'edad' => 'yearsOld', 'superpoder' => 'power'],
            $profile->fieldsByColumn(),
        );

        $this->assertEquals(
            ['clase' => ['Clase Alpha' => 'alpha', 'Clase Beta' => 'beta']],
            $profile->translationsByColumn(),
        );

        $this->assertEquals(
            ['unit' => 'kg', 'status' => 'active'],
            $profile->defaultValues(),
        );
    }

    public function testApplyToConfigurationsFillsOnlyMissingFieldsAndTranslationsByExactMatch(): void
    {
        $profile = ImportationProfileMother::from(
            importationProfileId: ImportationProfileId::generate(),
            type: 'measurements',
            accountId: AccountId::generate(),
            fieldsByColumn: ['nombre' => 'name', 'unknown-column' => 'ignored'],
            translationsByColumn: ['clase' => ['Clase Alpha' => 'alpha', 'Clase Beta' => 'beta']],
        );

        $configurations = [
            new ImportationColumnConfiguration(
                column: 0,
                name: 'nombre',
                field: null,
                values: [],
                translations: [],
            ),
            new ImportationColumnConfiguration(
                column: 1,
                name: 'edad',
                field: 'age',
                values: [],
                translations: [],
            ),
            new ImportationColumnConfiguration(
                column: 2,
                name: 'clase',
                field: 'class',
                values: [],
                translations: [
                    new ColumnValueTranslation(value: 'Clase Alpha', translation: null),
                    new ColumnValueTranslation(value: 'Clase Beta', translation: 'already-set'),
                    new ColumnValueTranslation(value: 'Clase Desconocida', translation: null),
                ],
            ),
        ];

        $result = $profile->applyToConfigurations($configurations);

        $this->assertSame('name', $result[0]->field);
        $this->assertSame('age', $result[1]->field);

        $this->assertSame('alpha', $result[2]->translations[0]->translation);
        $this->assertSame('already-set', $result[2]->translations[1]->translation);
        $this->assertNull($result[2]->translations[2]->translation);
    }
}
