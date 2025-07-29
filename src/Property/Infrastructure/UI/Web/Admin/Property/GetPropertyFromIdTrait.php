<?php

namespace App\Property\Infrastructure\UI\Web\Admin\Property;

use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Property\Domain\Model\Property\PropertyRepository;

trait GetPropertyFromIdTrait
{
    private function getPropertyFromId(string $propertyId, PropertyRepository $propertyRepository): Property
    {
        $property = $propertyRepository->find(new PropertyId($propertyId));

        if (null == $property) {
            throw $this->createNotFoundException(new TranslatableMessage("La propiedad no existe"));
        }

        return $property;
    }
}