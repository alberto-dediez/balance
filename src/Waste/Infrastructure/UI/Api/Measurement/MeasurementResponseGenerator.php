<?php

namespace App\Waste\Infrastructure\UI\Api\Measurement;

use Symfony\Contracts\Translation\TranslatorInterface;
use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Waste\Infrastructure\Symfony\Twig\WasteExtension;
use App\Waste\Infrastructure\Symfony\Twig\PropertyExtension;
use App\Waste\Infrastructure\Symfony\Twig\WasteManagerExtension;
use App\Waste\Domain\Model\Measurement\Measurement as ModelMeasurement;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse as ApiMeasurement;

class MeasurementResponseGenerator
{
    public function __construct(
        private PropertyExtension $propertyExtension,
        private WasteExtension $wasteExtension,
        private WasteManagerExtension $wasteManagerExtension,
        private TranslatorInterface $translator
    ) {
        
    }

    public function generate(ModelMeasurement $modelMeasurement)
    {
        return new ApiMeasurement(
            measurementId: (string) $modelMeasurement->id(),
            property: $this->propertyExtension->propertyName($modelMeasurement->propertyId()),
            waste: $this->wasteExtension->wasteName($modelMeasurement->wasteId()),
            wasteManager: $this->wasteManagerExtension->wasteManagerName($modelMeasurement->wasteManagerId()),
            quantity: $modelMeasurement->quantity(),
            load: $modelMeasurement->load(),
            unit: null !== $modelMeasurement->measurementMethod() 
                ? $modelMeasurement->measurementMethod()->unit() 
                : '',
            purpose: $this->translator->trans($modelMeasurement->purpose()->value, [], 'purpose'),
            justification: (string) $modelMeasurement->justification(),
            registeredAt: $modelMeasurement->registeredAt()->format('Y-m-d H:i:s')
        );
    }
}