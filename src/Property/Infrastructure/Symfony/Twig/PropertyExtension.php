<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Twig;

use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PropertyExtension extends AbstractExtension
{
    /** @var Property[] */
    private array $properties = [];

    public function __construct(private readonly PropertyRepository $propertyRepository) {}

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
            $property = $this->propertyRepository->find($propertyId);

            if ($property instanceof Property) {
                $this->properties[(string) $propertyId] = $property;
            }
        }

        return $this->properties[(string) $propertyId] ?? null;
    }
}
