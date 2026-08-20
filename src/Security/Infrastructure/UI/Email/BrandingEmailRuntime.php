<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Email;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Branding\BrandingTokens;
use App\Shared\Domain\Model\Theme\ThemeRegistry;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class BrandingEmailRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly BrandingEmailStylesheet $stylesheet,
        private readonly ThemeRegistry $themeRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Packages $packages,
        private readonly DefaultBrandingTokens $defaultTokens,
    ) {}

    public function css(?BrandingTokens $tokens): string
    {
        return ($this->stylesheet)($tokens ?? $this->defaultTokens->__invoke());
    }

    public function logoUrl(?BrandingTokens $tokens): string
    {
        $logoMediaId = $tokens?->logoMediaId;

        if (!$logoMediaId instanceof MediaId) {
            return $this->packages->getUrl('images/logo-dark.png');
        }

        return $this->urlGenerator->generate(
            'library_media_public_show',
            ['mediaId' => (string) $logoMediaId],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    public function themeName(?BrandingTokens $tokens): string
    {
        return $this->themeRegistry->get(null === $tokens ? 'default' : $tokens->themeSlug)->name();
    }
}
