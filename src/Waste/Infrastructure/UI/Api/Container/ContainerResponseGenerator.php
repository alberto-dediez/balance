<?php

namespace App\Waste\Infrastructure\UI\Api\Container;

use App\Waste\Domain\Model\Container\MeasurementMethod;
use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Waste\Infrastructure\Symfony\Twig\WasteExtension;
use App\Waste\Infrastructure\Symfony\Twig\WasteManagerExtension;
use App\Waste\Domain\Model\Container\Container as ModelContainer;
use App\Waste\Infrastructure\UI\Api\Container\ContainerResponse as ApiContainer;

class ContainerResponseGenerator
{
    public function __construct(
        private readonly PropertyExtension $propertyExtension,
        private readonly WasteExtension $wasteExtension,
        private readonly WasteManagerExtension $wasteManagerExtension,
        private readonly TranslatorInterface $translator
    ) {

    }

    public function generate(ModelContainer $modelContainer): ApiContainer
    {
        return new ApiContainer(
            containerId: (string) $modelContainer->id(),
            property: $this->propertyExtension->propertyName($modelContainer->propertyId()),
            waste: $this->wasteExtension->wasteName($modelContainer->wasteId()),
            wasteManager: $this->wasteManagerExtension->wasteManagerName($modelContainer->wasteManagerId()),
            unit: $modelContainer->measurementMethod() instanceof MeasurementMethod
                ? $modelContainer->measurementMethod()->unit()
                : '',
            purpose: $this->translator->trans($modelContainer->purpose()->value, [], 'purpose'),
        );
    }
}
