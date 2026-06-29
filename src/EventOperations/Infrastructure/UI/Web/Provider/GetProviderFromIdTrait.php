<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\UI\Web\Provider;

use App\EventOperations\Domain\Model\Provider\Provider;
use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\EventOperations\Domain\Model\Provider\ProviderRepository;

trait GetProviderFromIdTrait
{
    private function getProviderFromId(string $providerId, ProviderRepository $repository): Provider
    {
        $provider = $repository->find(new ProviderId($providerId));

        if (null === $provider) {
            throw $this->createNotFoundException('Proveedor no encontrado');
        }

        return $provider;
    }
}
