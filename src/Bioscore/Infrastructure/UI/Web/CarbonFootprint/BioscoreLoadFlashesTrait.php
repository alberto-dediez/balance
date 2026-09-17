<?php

declare(strict_types=1);

namespace App\Bioscore\Infrastructure\UI\Web\CarbonFootprint;

use App\Bioscore\Application\BioscoreConsumption\SkippedBioscoreConsumption;
use App\CarbonFootprint\Domain\Model\CarbonFootprint\Source;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

trait BioscoreLoadFlashesTrait
{
    /**
     * @param list<SkippedBioscoreConsumption> $skipped
     * @param list<Source>                     $includedSources
     */
    private function flashResult(TranslatorInterface $translator, bool $resolved, int $activities, int $properties, array $skipped, array $includedSources): void
    {
        if (!$resolved) {
            $this->addFlash('warning', new TranslatableMessage('Ninguna propiedad de esta huella está vinculada a Bioscore'));

            return;
        }

        $this->addFlash('success', new TranslatableMessage('Consumos de Bioscore cargados: {activities} actividades en {properties} propiedad(es)', [
            '{activities}' => $activities,
            '{properties}' => $properties,
        ]));

        if ([] !== $includedSources) {
            $this->addFlash('warning', new TranslatableMessage('Se han vuelto a incluir las fuentes: {sources}', [
                '{sources}' => implode(', ', array_map(
                    static fn(Source $source): string => $translator->trans($source->value, [], 'source'),
                    $includedSources,
                )),
            ]));
        }

        if ([] !== $skipped) {
            $this->addFlash('warning', new TranslatableMessage('Consumos de Bioscore omitidos ({count}): {items}', [
                '{count}' => count($skipped),
                '{items}' => implode('; ', array_map(
                    static fn(SkippedBioscoreConsumption $item): string => sprintf(
                        '%s (#%d, %s): %s',
                        $item->name,
                        $item->bioscoreId,
                        trim(sprintf('%s %s', rtrim(rtrim(number_format($item->consumption, 4, ',', '.'), '0'), ','), $item->unit)),
                        $translator->trans($item->reason->value, [], 'bioscoreConsumptionSkipReason'),
                    ),
                    $skipped,
                )),
            ]));
        }
    }
}
