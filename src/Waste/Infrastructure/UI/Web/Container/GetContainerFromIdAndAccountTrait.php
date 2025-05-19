<?php

namespace App\Waste\Infrastructure\UI\Web\Container;

use App\Waste\Domain\Model\Container\Container;
use App\Security\Domain\Model\Account\AccountId;
use App\Waste\Domain\Model\Container\ContainerId;
use Symfony\Component\Translation\TranslatableMessage;
use App\Waste\Domain\Model\Container\ContainerRepository;

trait GetContainerFromIdAndAccountTrait
{
    private function getContainerFromIdAndAccount(string $containerId, ?AccountId $accountId, ContainerRepository $containerRepository): Container
    {
        $container = $containerRepository->find(new ContainerId($containerId));

        if (null == $container) {
            throw $this->createNotFoundException(new TranslatableMessage("El contenedor no existe"));
        }

        if (null == $container->accountId() || null == $accountId || !$container->accountId()->equals($accountId)) {
            throw $this->createNotFoundException(new TranslatableMessage("El contenedor no existe"));
        }

        return $container;
    }
}