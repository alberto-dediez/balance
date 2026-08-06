<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Component;

use App\CarbonFootprint\Application\TripCalculationLink\Register\RegisterTripCalculationLinkRequest;
use App\CarbonFootprint\Application\TripCalculationLink\Register\RegisterTripCalculationLinkResponse;
use App\CarbonFootprint\Application\TripCalculationLink\Register\RegisterTripCalculationLinkService;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Qr\QrCodeGenerator;
use App\Shared\Infrastructure\Qr\QrCodeImage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('CarbonFootprint:PropertyTripQr', template: '@components/CarbonFootprint/PropertyTripQr.html.twig')]
final class PropertyTripQr
{
    public string $propertyId;

    private ?RegisterTripCalculationLinkResponse $registerTripCalculationLinkResponse = null;

    public function __construct(
        private readonly RegisterTripCalculationLinkService $registerTripCalculationLink,
        private readonly LoggedUser $loggedUser,
        private readonly QrCodeGenerator $qrCodeGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function url(): string
    {
        return $this->urlGenerator->generate(
            'public_carbon_footprint_property_trip',
            ['token' => $this->tripCalculationLinkResponse()->tripCalculationLink->token()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    public function qrCode(): QrCodeImage
    {
        return $this->qrCodeGenerator->generate($this->url());
    }

    public function resultsPath(): string
    {
        return $this->urlGenerator->generate(
            'carbon_footprint_property_trip_calculations',
            ['propertyId' => $this->propertyId],
        );
    }

    private function tripCalculationLinkResponse(): RegisterTripCalculationLinkResponse
    {
        return $this->registerTripCalculationLinkResponse ??= ($this->registerTripCalculationLink)(
            new RegisterTripCalculationLinkRequest(new PropertyId($this->propertyId)),
            ($this->loggedUser)(),
        );
    }
}
