<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\Remove;

use App\Security\Domain\Model\Branding\BrandingId;
use App\Shared\Application\CommandBus\Command;

class RemoveBrandingRequest implements Command
{
    public function __construct(public BrandingId $brandingId) {}
}
