<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Application\Branding;

use App\Security\Application\Branding\BrandingAuthorizer;

class BrandingAuthorizerMother
{
    public static function create(): BrandingAuthorizer
    {
        return new BrandingAuthorizer();
    }
}
