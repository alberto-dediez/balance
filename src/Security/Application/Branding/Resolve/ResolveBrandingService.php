<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\Resolve;

use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Domain\Model\Branding\BrandingTokens;
use App\Shared\Domain\Model\Theme\ThemeRegistry;

class ResolveBrandingService implements BrandingResolver
{
    public function __construct(
        private BrandingRepository $repository,
        private ThemeRegistry $themeRegistry,
        private BrandingPalette $palette,
    ) {}

    public function resolve(?AccountId $accountId): BrandingTokens
    {
        $branding = $accountId instanceof AccountId ? $this->repository->findByAccountId($accountId) : null;

        $theme = $branding instanceof Branding
            ? $this->themeRegistry->get($branding->themeSlug())
            : $this->themeRegistry->default();

        return $this->palette->resolve($branding, $theme->defaults(), $theme->slug());
    }
}
