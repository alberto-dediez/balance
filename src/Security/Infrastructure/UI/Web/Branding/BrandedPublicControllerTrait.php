<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Branding;

use App\Security\Domain\Model\Account\AccountId;
use Symfony\Contracts\Service\Attribute\Required;

trait BrandedPublicControllerTrait
{
    private BrandingContext $brandingContext;

    #[Required]
    public function setBrandingContext(BrandingContext $brandingContext): void
    {
        $this->brandingContext = $brandingContext;
    }

    private function brandFor(?AccountId $accountId): void
    {
        $this->brandingContext->useAccount($accountId);
    }
}
