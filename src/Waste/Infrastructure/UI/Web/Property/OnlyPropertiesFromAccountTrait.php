<?php

namespace App\Waste\Infrastructure\UI\Web\Property;

use App\Waste\Domain\Model\Property\Property;
use App\Security\Domain\Model\Account\Account;
use Symfony\Component\Translation\TranslatableMessage;

trait OnlyPropertiesFromAccountTrait
{
    protected function onlyPropertiesFromAccount(?Property $property, ?Account $account)
    {
        if (null == $property || null == $account || null == $property->accountId() || false == $account->id()->equals($property->accountId())) {
            throw $this->createNotFoundException(new TranslatableMessage("La propiedad no existe"));
        }
    }
}
