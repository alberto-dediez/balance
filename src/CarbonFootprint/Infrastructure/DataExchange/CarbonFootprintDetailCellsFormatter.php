<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRow;
use App\CarbonFootprint\Application\CarbonFootprint\Export\CarbonFootprintDetailRowType;
use App\CarbonFootprint\Domain\Model\Activity\ItemCondition;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CarbonFootprintDetailCellsFormatter
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * @param list<string> $excludedColumns
     *
     * @return list<ExportColumnDefinition>
     */
    public function commonColumns(ExportColumnDefinition $stageColumn, array $excludedColumns = []): array
    {
        $columns = [
            new ExportColumnDefinition('rowType', new TranslatableMessage('Tipo de fila')),
            $stageColumn,
            new ExportColumnDefinition('source', new TranslatableMessage('Categoría')),
            new ExportColumnDefinition('property', new TranslatableMessage('Propiedad')),
            new ExportColumnDefinition('activityType', new TranslatableMessage('Tipo de actividad')),
            new ExportColumnDefinition('item', new TranslatableMessage('Elemento')),
            new ExportColumnDefinition('origin', new TranslatableMessage('Origen')),
            new ExportColumnDefinition('provider', new TranslatableMessage('Proveedor')),
            new ExportColumnDefinition('dataSource', new TranslatableMessage('Descripción / origen del dato')),
            new ExportColumnDefinition('quantity', new TranslatableMessage('Cantidad')),
            new ExportColumnDefinition('unit', new TranslatableMessage('Unidad')),
            new ExportColumnDefinition('condition', new TranslatableMessage('Condición'), false),
            new ExportColumnDefinition('nights', new TranslatableMessage('Noches'), false),
            new ExportColumnDefinition('people', new TranslatableMessage('Personas'), false),
            new ExportColumnDefinition('weightTonnes', new TranslatableMessage('Peso (t)'), false),
            new ExportColumnDefinition('distanceKm', new TranslatableMessage('Distancia (km)'), false),
            new ExportColumnDefinition('containerCapacityLitres', new TranslatableMessage('Capacidad del contenedor (l)'), false),
            new ExportColumnDefinition('fillPercentage', new TranslatableMessage('% de llenado'), false),
            new ExportColumnDefinition('acquisitionYear', new TranslatableMessage('Año de adquisición'), false),
            new ExportColumnDefinition('factorCo2eq', new TranslatableMessage('Factor de emisión (kg CO₂eq)')),
            new ExportColumnDefinition('factorUnit', new TranslatableMessage('Unidad del factor')),
            new ExportColumnDefinition('factorYear', new TranslatableMessage('Año del factor')),
            new ExportColumnDefinition('factorSource', new TranslatableMessage('Fuente del factor')),
            new ExportColumnDefinition('emissionsCo2eq', new TranslatableMessage('Emisiones (kg CO₂eq)')),
            new ExportColumnDefinition('emissionsCo2', new TranslatableMessage('CO₂ (kg)'), false),
            new ExportColumnDefinition('emissionsCh4', new TranslatableMessage('CH₄ (kg)'), false),
            new ExportColumnDefinition('emissionsN2o', new TranslatableMessage('N₂O (kg)'), false),
            new ExportColumnDefinition('wellToTankCo2eq', new TranslatableMessage('Emisiones WTT (kg CO₂eq)'), false),
            new ExportColumnDefinition('locationBasedCo2eq', new TranslatableMessage('Emisiones por localización (kg CO₂eq)')),
            new ExportColumnDefinition('manualCo2eq', new TranslatableMessage('Valor manual (kg CO₂eq)'), false),
            new ExportColumnDefinition('exclusionReason', new TranslatableMessage('Motivo de exclusión')),
            new ExportColumnDefinition('exclusionComment', new TranslatableMessage('Comentario de exclusión'), false),
            new ExportColumnDefinition('registeredAt', new TranslatableMessage('Fecha de registro'), false),
            new ExportColumnDefinition('updatedAt', new TranslatableMessage('Fecha de modificación'), false),
            new ExportColumnDefinition('activityId', new TranslatableMessage('Id de actividad'), false),
        ];

        return array_values(array_filter(
            $columns,
            static fn(ExportColumnDefinition $column): bool => !in_array($column->key, $excludedColumns, true),
        ));
    }

    /**
     * @param list<string> $excludedColumns
     *
     * @return array<string, scalar>
     */
    public function cells(CarbonFootprintDetailRow $row, string $stageColumn, string $stageDomain, array $excludedColumns = []): array
    {
        $cells = [
            'rowType' => $this->translator->trans($row->rowType->value, [], 'carbonFootprintDetailRowType'),
            $stageColumn => $this->translator->trans($row->stage->key(), [], $stageDomain),
            'source' => null === $row->source ? $this->translator->trans('Alcance 3 completo') : $this->translator->trans($row->source->value, [], 'source'),
            'property' => $row->propertyName ?? '',
            'activityType' => null === $row->activityType ? '' : $this->translator->trans($row->activityType->value, [], 'activityType'),
            'item' => $row->itemName ?? '',
            'origin' => $this->origin($row),
            'provider' => $row->providerName ?? '',
            'dataSource' => $row->dataSource ?? '',
            'quantity' => $row->quantity ?? '',
            'unit' => null === $row->unit ? '' : $this->translator->trans($row->unit->value, [], 'unitType'),
            'condition' => $this->condition($row->condition),
            'nights' => $row->nights ?? '',
            'people' => $row->people ?? '',
            'weightTonnes' => $row->weightTonnes ?? '',
            'distanceKm' => $row->distanceKm ?? '',
            'containerCapacityLitres' => $row->containerCapacityLitres ?? '',
            'fillPercentage' => $row->fillPercentage ?? '',
            'acquisitionYear' => $row->acquisitionYear ?? '',
            'factorCo2eq' => $row->factorCo2eq ?? '',
            'factorUnit' => null === $row->factorUnit ? '' : $this->translator->trans($row->factorUnit->value, [], 'unitType'),
            'factorYear' => $row->factorYear ?? '',
            'factorSource' => $row->factorSource ?? '',
            'emissionsCo2eq' => $row->emissionsCo2eq ?? '',
            'emissionsCo2' => $row->emissionsCo2 ?? '',
            'emissionsCh4' => $row->emissionsCh4 ?? '',
            'emissionsN2o' => $row->emissionsN2o ?? '',
            'wellToTankCo2eq' => $row->wellToTankCo2eq ?? '',
            'locationBasedCo2eq' => $row->locationBasedCo2eq ?? '',
            'manualCo2eq' => $row->manualCo2eq ?? '',
            'exclusionReason' => null === $row->exclusionReason ? '' : $this->translator->trans($row->exclusionReason->value, [], 'exclusionReason'),
            'exclusionComment' => $row->exclusionComment ?? '',
            'registeredAt' => $row->registeredAt?->format('d/m/Y H:i') ?? '',
            'updatedAt' => $row->updatedAt?->format('d/m/Y H:i') ?? '',
            'activityId' => $row->activityId ?? '',
        ];

        return array_diff_key($cells, array_flip($excludedColumns));
    }

    private function origin(CarbonFootprintDetailRow $row): string
    {
        if (CarbonFootprintDetailRowType::Activity !== $row->rowType && CarbonFootprintDetailRowType::DerivedFuelAndEnergy !== $row->rowType) {
            return '';
        }

        if ($row->fromEmployees) {
            return $this->translator->trans('Empleados');
        }

        return null !== $row->providerName ? $this->translator->trans('Proveedor') : $this->translator->trans('Propia');
    }

    private function condition(?ItemCondition $condition): string
    {
        return match ($condition) {
            ItemCondition::New => $this->translator->trans('Nuevo'),
            ItemCondition::Recycled => $this->translator->trans('Reciclado'),
            null => '',
        };
    }
}
