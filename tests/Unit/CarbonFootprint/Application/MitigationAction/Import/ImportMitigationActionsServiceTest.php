<?php

declare(strict_types=1);

namespace App\Tests\Unit\CarbonFootprint\Application\MitigationAction\Import;

use App\CarbonFootprint\Application\MitigationAction\Import\ImportMitigationActionsRequest;
use App\CarbonFootprint\Application\MitigationAction\MitigationPlanNotEditableException;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\CarbonFootprint\Domain\Model\Emissions;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationAction;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionId;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\Property\Domain\Model\Property\PropertyId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Locale\Locale;
use App\Tests\Unit\CarbonFootprint\Application\MitigationAction\MitigationPlanContextTrait;
use App\Tests\Unit\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionMother;
use PHPUnit\Framework\TestCase;

class ImportMitigationActionsServiceTest extends TestCase
{
    use MitigationPlanContextTrait;

    private PropertyId $propertyId;

    protected function setUp(): void
    {
        DomainEventPublisher::instance()->reset();
        $this->initMitigationPlan();
        $this->propertyId = PropertyId::generate();
    }

    public function testOnlyIfEmptyDoesNothingWhenThereAreActions(): void
    {
        $previous = $this->propertyFootprint(2024, $this->propertyId);
        $this->actionFor($previous, 'Previa', 2024, 2030);
        $current = $this->propertyFootprint(2025, $this->propertyId);
        $existing = $this->actionFor($current, 'Actual', 2025, 2030);

        $response = ($this->importService())($this->request($current, true), $this->owner());

        $this->assertSame(0, $response->imported);
        $this->assertFalse($existing->isRemoved());
        $this->assertCount(1, $this->actionsOf($current));
    }

    public function testOnlyIfEmptyImportsWhenThePlanIsEmpty(): void
    {
        $previous = $this->propertyFootprint(2024, $this->propertyId);
        $this->actionFor($previous, 'Previa', 2024, 2030);
        $current = $this->propertyFootprint(2025, $this->propertyId);

        $response = ($this->importService())($this->request($current, true), $this->owner());

        $this->assertSame(1, $response->imported);
        $this->assertCount(1, $this->actionsOf($current));
    }

    public function testWithoutOnlyIfEmptyRemovesTheCurrentActionsEvenWithoutPreviousFootprint(): void
    {
        $current = $this->propertyFootprint(2025, $this->propertyId);
        $existing = $this->actionFor($current, 'Actual', 2025, 2030);

        $response = ($this->importService())($this->request($current, false), $this->owner());

        $this->assertSame(0, $response->imported);
        $this->assertTrue($existing->isRemoved());
        $this->assertCount(0, $this->actionsOf($current));
    }

    public function testImportsOnlyActionsWithTargetYearAfterTheFootprintYear(): void
    {
        $previous = $this->propertyFootprint(2024, $this->propertyId);
        $this->actionFor($previous, 'Vigente', 2024, 2027);
        $this->actionFor($previous, 'Terminada', 2023, 2025);
        $current = $this->propertyFootprint(2025, $this->propertyId);

        $response = ($this->importService())($this->request($current, false), $this->owner());

        $this->assertSame(1, $response->imported);
        $this->assertSame(['Vigente'], array_map(static fn(MitigationAction $action): string => $action->defaultName(), $this->actionsOf($current)));
    }

    public function testTakesTheMostRecentPreviousFootprint(): void
    {
        $oldest = $this->propertyFootprint(2022, $this->propertyId);
        $this->actionFor($oldest, 'Antigua', 2022, 2030);
        $previous = $this->propertyFootprint(2024, $this->propertyId);
        $this->actionFor($previous, 'Reciente', 2024, 2030);
        $this->propertyFootprint(2026, $this->propertyId);
        $current = $this->propertyFootprint(2025, $this->propertyId);

        ($this->importService())($this->request($current, false), $this->owner());

        $this->assertSame(['Reciente'], array_map(static fn(MitigationAction $action): string => $action->defaultName(), $this->actionsOf($current)));
    }

    public function testKeepsYearsRangesMeasureAndTranslations(): void
    {
        $previous = $this->propertyFootprint(2024, $this->propertyId);
        $measureId = MitigationMeasureId::generate();
        $action = MitigationActionMother::from(
            id: (string) MitigationActionId::generate(),
            carbonFootprintId: (string) $previous->id(),
            name: 'Caldera',
            baseYear: 2023,
            targetYear: 2029,
            measureId: $measureId,
        );
        $action->translate('Boiler', 'Replace it', Locale::English);
        $action->setEffect(Source::FixedFacilities, (string) $this->naturalGasId, 'Gas', 0.15, 0.1, 0.3);
        $this->mitigationActions->add($action);
        $current = $this->propertyFootprint(2025, $this->propertyId);

        ($this->importService())($this->request($current, false), $this->owner());

        $imported = $this->actionsOf($current)[0];
        $this->assertSame(2023, $imported->baseYear());
        $this->assertSame(2029, $imported->targetYear());
        $this->assertTrue($measureId->equals($imported->measureId() ?? MitigationMeasureId::generate()));
        $this->assertSame('Caldera', $imported->defaultName());
        $this->assertNotNull($imported->translations()->getTranslationFor(Locale::English));
        $effect = $imported->effects()->forSourceAndItem(Source::FixedFacilities, (string) $this->naturalGasId);
        $this->assertNotNull($effect);
        $this->assertSame(0.15, $effect->reduction());
        $this->assertSame(0.1, $effect->minimumReduction());
        $this->assertSame(0.3, $effect->maximumReduction());
    }

    public function testRejectsACompletedFootprint(): void
    {
        $current = $this->propertyFootprint(2025, $this->propertyId);
        $current->complete(new Emissions('1500', '1500', '0', '0'));

        $this->expectException(MitigationPlanNotEditableException::class);

        ($this->importService())($this->request($current, false), $this->owner());
    }

    private function request(PropertyCarbonFootprint $footprint, bool $onlyIfEmpty): ImportMitigationActionsRequest
    {
        return new ImportMitigationActionsRequest(CarbonFootprintReportSubjectType::Property, (string) $footprint->id(), $onlyIfEmpty);
    }

    private function actionFor(PropertyCarbonFootprint $footprint, string $name, int $baseYear, int $targetYear): MitigationAction
    {
        $action = MitigationActionMother::manual(carbonFootprintId: (string) $footprint->id(), name: $name, baseYear: $baseYear, targetYear: $targetYear);
        $this->mitigationActions->add($action);

        return $action;
    }

    /** @return array<MitigationAction> */
    private function actionsOf(PropertyCarbonFootprint $footprint): array
    {
        return $this->mitigationActions->findByFilter(new MitigationActionFilter(
            subjectType: CarbonFootprintReportSubjectType::Property,
            carbonFootprintId: (string) $footprint->id(),
            limit: 0,
        ));
    }
}
