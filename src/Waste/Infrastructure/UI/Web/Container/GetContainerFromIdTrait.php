<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\UI\Web\Container;

use App\Waste\Domain\Model\Container\Container;
use App\Waste\Domain\Model\Container\ContainerId;
use App\Waste\Domain\Model\Container\ContainerRepository;

trait GetContainerFromIdTrait
{
    private function getContainerFromId(string $containerId, ContainerRepository $containerRepository): Container
    {
        $container = $containerRepository->find(new ContainerId($containerId));

        if (null == $container) {
            throw $this->createNotFoundException('El contenedor no existe');
        }

        return $container;
    }
}
