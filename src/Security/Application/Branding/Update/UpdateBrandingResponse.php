<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\Update;

use App\Security\Domain\Model\Branding\BrandingId;
use App\Shared\Application\CommandBus\CommandResponse;

class UpdateBrandingResponse implements CommandResponse
{
    public function __construct(public BrandingId $brandingId) {}
}
