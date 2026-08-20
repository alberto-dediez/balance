<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Email;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BrandingEmailExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('branding_email_css', [BrandingEmailRuntime::class, 'css']),
            new TwigFunction('branding_email_logo_url', [BrandingEmailRuntime::class, 'logoUrl']),
        ];
    }
}
