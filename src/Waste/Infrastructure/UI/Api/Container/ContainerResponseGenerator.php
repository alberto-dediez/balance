<?php

namespace App\Waste\Infrastructure\UI\Api\Container;

use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Waste\Infrastructure\Symfony\Twig\WasteExtension;
use App\Waste\Infrastructure\Symfony\Twig\WasteManagerExtension;
use App\Waste\Domain\Model\Container\Container as ModelContainer;
use App\Waste\Infrastructure\UI\Api\Container\ContainerResponse as ApiContainer;

class ContainerResponseGenerator
{
    public function __construct(
        private PropertyExtension $propertyExtension,
        private WasteExtension $wasteExtension,
        private WasteManagerExtension $wasteManagerExtension,
        private TranslatorInterface $translator
    ) {

    }

    public function generate(ModelContainer $modelContainer)
    {
        return new ApiContainer(
            containerId: (string) $modelContainer->id(),
            property: $this->propertyExtension->propertyName($modelContainer->propertyId()),
            waste: $this->wasteExtension->wasteName($modelContainer->wasteId()),
            wasteManager: $this->wasteManagerExtension->wasteManagerName($modelContainer->wasteManagerId()),
            unit: null !== $modelContainer->measurementMethod()
                ? $modelContainer->measurementMethod()->unit()
                : '',
            purpose: $this->translator->trans($modelContainer->purpose()->value, [], 'purpose'),
        );
    }
}
