<?php

namespace App\Waste\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use App\Waste\Domain\Model\Property\PropertyRepository;

class PropertyExtension extends AbstractExtension
{
    private array $properties = [];

    public function __construct(private PropertyRepository $propertyRepository)
    {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('property_name', [$this, 'propertyName']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            
        ];
    }

    public function propertyName(PropertyId $propertyId): ?Property
    {
        if (!isset($properties[(string) $propertyId])) {
            $properties[(string) $propertyId] = $this->propertyRepository->find($propertyId);
        }

        return $properties[(string) $propertyId];
    }
}
