<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Api\Container;

use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use App\Waste\Domain\Model\Container\Container as ModelContainer;
use App\Waste\Infrastructure\Symfony\Twig\WasteExtension;
use App\Waste\Infrastructure\Symfony\Twig\WasteManagerExtension;
use App\Waste\Infrastructure\UI\Api\Container\ContainerResponse as ApiContainer;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContainerResponseGenerator
{
    public function __construct(
        private readonly PropertyExtension $propertyExtension,
        private readonly WasteExtension $wasteExtension,
        private readonly WasteManagerExtension $wasteManagerExtension,
        private readonly TranslatorInterface $translator,
    ) {}

    public function generate(ModelContainer $modelContainer): ApiContainer
    {
        $propertyId = $modelContainer->propertyId();
        assert(null !== $propertyId);
        $wasteId = $modelContainer->wasteId();
        assert(null !== $wasteId);
        $purpose = $modelContainer->purpose();

        return new ApiContainer(
            containerId: (string) $modelContainer->id(),
            property: (string) $this->propertyExtension->propertyName($propertyId),
            waste: (string) $this->wasteExtension->wasteName($wasteId),
            wasteManager: (string) $this->wasteManagerExtension->wasteManagerName($modelContainer->wasteManagerId()),
            unit: $modelContainer->measurementMethod()->unit(),
            purpose: $this->translator->trans($purpose->value, [], 'purpose'),
        );
    }
}
