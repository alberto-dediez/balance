<?php

namespace App\Property\Infrastructure\Symfony\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;

class PropertyExtension extends AbstractExtension
{
    private array $properties = [];

    public function __construct(private readonly PropertyRepository $propertyRepository)
    {

    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('property_name', $this->propertyName(...)),
        ];
    }

    public function getFunctions(): array
    {
        return [
            
        ];
    }

    public function propertyName(PropertyId $propertyId): ?Property
    {
        if (!isset($this->properties[(string) $propertyId])) {
            $this->properties[(string) $propertyId] = $this->propertyRepository->find($propertyId);
        }

        return $this->properties[(string) $propertyId];
    }
}
