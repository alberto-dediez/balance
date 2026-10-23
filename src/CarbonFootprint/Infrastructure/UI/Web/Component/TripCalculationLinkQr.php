<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Component;

use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PublicTripRoutes;
use App\Shared\Infrastructure\Qr\QrCodeGenerator;
use App\Shared\Infrastructure\Qr\QrCodeImage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('CarbonFootprint:TripCalculationLinkQr', template: '@components/CarbonFootprint/TripCalculationLinkQr.html.twig')]
final class TripCalculationLinkQr
{
    public string $token;

    public string $prefix = PublicTripRoutes::ACCOUNT_PREFIX;

    public function __construct(
        private readonly QrCodeGenerator $qrCodeGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function url(): string
    {
        return $this->urlGenerator->generate(
            $this->prefix,
            ['token' => $this->token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    public function qrCode(): QrCodeImage
    {
        return $this->qrCodeGenerator->generate($this->url());
    }
}
