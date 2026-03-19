<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\Admin\Property;

use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Translation\TranslatableMessage;

trait GetPropertyFromIdTrait
{
    private function getPropertyFromId(string $propertyId, PropertyRepository $propertyRepository): Property
    {
        $property = $propertyRepository->find(new PropertyId($propertyId));

        if (null == $property) {
            throw $this->createNotFoundException(new TranslatableMessage('La propiedad no existe'));
        }

        return $property;
    }
}
