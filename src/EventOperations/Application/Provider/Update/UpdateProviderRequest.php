<?php

declare(strict_types=1);

namespace App\EventOperations\Application\Provider\Update;

use App\EventOperations\Domain\Model\Provider\ProviderId;
use App\Shared\Application\CommandBus\Command;

class UpdateProviderRequest implements Command
{
    public function __construct(
        public ProviderId $providerId,
        public string $name,
        public string $vatNumber,
        public string $street,
        public string $postalCode,
        public string $city,
        public string $country,
    ) {}
}
