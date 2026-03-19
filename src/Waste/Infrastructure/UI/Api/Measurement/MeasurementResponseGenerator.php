<?php

namespace App\Waste\Infrastructure\UI\Api\Measurement;

use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Waste\Infrastructure\Symfony\Twig\WasteExtension;
use App\Waste\Infrastructure\Symfony\Twig\WasteManagerExtension;
use App\Waste\Domain\Model\Measurement\Measurement as ModelMeasurement;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse as ApiMeasurement;

class MeasurementResponseGenerator
{
    public function __construct(
        private readonly PropertyExtension $propertyExtension,
        private readonly WasteExtension $wasteExtension,
        private readonly WasteManagerExtension $wasteManagerExtension,
        private readonly TranslatorInterface $translator
    ) {

    }

    public function generate(ModelMeasurement $modelMeasurement): ApiMeasurement
    {
        return new ApiMeasurement(
            measurementId: (string) $modelMeasurement->id(),
            property: (string) $this->propertyExtension->propertyName($modelMeasurement->propertyId()),
            waste: (string) $this->wasteExtension->wasteName($modelMeasurement->wasteId()),
            wasteManager: (string) $this->wasteManagerExtension->wasteManagerName($modelMeasurement->wasteManagerId()),
            quantity: $modelMeasurement->quantity(),
            load: $modelMeasurement->load(),
            unit: $modelMeasurement->measurementMethod() instanceof MeasurementMethod
                ? $modelMeasurement->measurementMethod()->unit()
                : '',
            purpose: $this->translator->trans($modelMeasurement->purpose()->value, [], 'purpose'),
            justification: (string) $modelMeasurement->justification(),
            registeredAt: $modelMeasurement->registeredAt()->format('Y-m-d H:i:s')
        );
    }
}
