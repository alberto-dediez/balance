<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Component;

use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyRequest;
use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyResponse;
use App\CarbonFootprint\Application\AttendeeSurvey\Register\RegisterAttendeeSurveyService;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\Qr\QrCodeGenerator;
use App\Shared\Infrastructure\Qr\QrCodeImage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('CarbonFootprint:AttendeeSurveyQr', template: '@components/CarbonFootprint/AttendeeSurveyQr.html.twig')]
final class AttendeeSurveyQr
{
    public string $eventId;

    private ?RegisterAttendeeSurveyResponse $registerAttendeeSurveyResponse = null;

    public function __construct(
        private readonly RegisterAttendeeSurveyService $registerAttendeeSurvey,
        private readonly LoggedUser $loggedUser,
        private readonly QrCodeGenerator $qrCodeGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function url(): string
    {
        return $this->urlGenerator->generate(
            'public_carbon_footprint_attendee_survey',
            ['token' => $this->attendeeSurveyResponse()->attendeeSurvey->token()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    public function qrCode(): QrCodeImage
    {
        return $this->qrCodeGenerator->generate($this->url());
    }

    private function attendeeSurveyResponse(): RegisterAttendeeSurveyResponse
    {
        return $this->registerAttendeeSurveyResponse ??= ($this->registerAttendeeSurvey)(
            new RegisterAttendeeSurveyRequest(new EventId($this->eventId)),
            ($this->loggedUser)(),
        );
    }
}
