<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\Measurement;

use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use App\Waste\Domain\Model\Measurement\Measurement as ModelMeasurement;
use App\Waste\Infrastructure\Symfony\Twig\WasteExtension;
use App\Waste\Infrastructure\Symfony\Twig\WasteManagerExtension;
use App\Waste\Infrastructure\UI\Api\Measurement\MeasurementResponse as ApiMeasurement;
use Symfony\Contracts\Translation\TranslatorInterface;

class MeasurementResponseGenerator
{
    public function __construct(
        private readonly PropertyExtension $propertyExtension,
        private readonly WasteExtension $wasteExtension,
        private readonly WasteManagerExtension $wasteManagerExtension,
        private readonly TranslatorInterface $translator,
    ) {}

    public function generate(ModelMeasurement $modelMeasurement): ApiMeasurement
    {
        $propertyId = $modelMeasurement->propertyId();
        assert(null !== $propertyId);
        $wasteId = $modelMeasurement->wasteId();
        assert(null !== $wasteId);
        $purpose = $modelMeasurement->purpose();

        return new ApiMeasurement(
            measurementId: (string) $modelMeasurement->id(),
            property: (string) $this->propertyExtension->propertyName($propertyId),
            waste: (string) $this->wasteExtension->wasteName($wasteId),
            wasteManager: (string) $this->wasteManagerExtension->wasteManagerName($modelMeasurement->wasteManagerId()),
            quantity: $modelMeasurement->quantity(),
            load: (string) $modelMeasurement->load(),
            unit: $modelMeasurement->measurementMethod()->unit(),
            purpose: $this->translator->trans($purpose->value, [], 'purpose'),
            justification: (string) $modelMeasurement->justification(),
            registeredAt: $modelMeasurement->registeredAt()->format('Y-m-d H:i:s'),
        );
    }
}
