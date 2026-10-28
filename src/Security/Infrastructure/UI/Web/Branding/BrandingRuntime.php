<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Branding;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Branding\FontFamilyName;
use App\Shared\Domain\Model\Theme\ThemeRegistry;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class BrandingRuntime implements RuntimeExtensionInterface
{
    private const string GOOGLE_FONTS_BASE = 'https://fonts.googleapis.com/css2';

    public function __construct(
        private readonly BrandingContext $context,
        private readonly ThemeRegistry $themeRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Packages $packages,
    ) {}

    public function cssVariables(): string
    {
        $declarations = [];

        foreach ($this->context->tokens()->cssVariables() as $name => $value) {
            $declarations[] = sprintf('%s:%s', $name, $value);
        }

        return implode(';', $declarations) . ';';
    }

    public function googleFontsUrl(): ?string
    {
        $tokens = $this->context->tokens();

        if (!$tokens->webFontFamily instanceof FontFamilyName) {
            return null;
        }

        $family = str_replace(' ', '+', $tokens->webFontFamily->value);
        $weights = $this->sanitiseWeights($tokens->webFontWeights);

        return sprintf(
            '%s?family=%s%s&display=swap',
            self::GOOGLE_FONTS_BASE,
            $family,
            null === $weights ? '' : ':wght@' . $weights,
        );
    }

    public function logoUrl(): string
    {
        $tokens = $this->context->tokens();
        $logoMediaId = $tokens->logoMediaId;

        if (!$logoMediaId instanceof MediaId) {
            return $this->packages->getUrl($this->themeRegistry->get($tokens->themeSlug)->logo() ?? 'images/logo-dark.png');
        }

        return $this->urlGenerator->generate('library_media_public_show', ['mediaId' => (string) $logoMediaId]);
    }

    public function stylesheet(): ?string
    {
        $stylesheet = $this->themeRegistry->get($this->context->tokens()->themeSlug)->stylesheet();

        return null === $stylesheet ? null : $this->packages->getUrl($stylesheet);
    }

    /**
     * @return list<string>
     */
    public function themeTemplate(string $name): array
    {
        $themeSlug = $this->context->tokens()->themeSlug;

        if ('default' === $themeSlug) {
            return [$name];
        }

        return [
            sprintf('@themes/%s/%s', $themeSlug, ltrim($name, '@')),
            $name,
        ];
    }

    private function sanitiseWeights(?string $weights): ?string
    {
        if (null === $weights || 1 !== preg_match('/^[0-9;]{1,50}$/', $weights)) {
            return null;
        }

        return $weights;
    }
}
