<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\AttendeeSurvey\Export\EventAttendeeSurveysExportFilter;
use App\CarbonFootprint\Application\Contribution\OriginCompensation;
use App\CarbonFootprint\Application\Contribution\OriginCompensationsCalculator;
use App\CarbonFootprint\Domain\Model\AttendeeSurvey\AttendeeSurveyRepository;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponse;
use App\CarbonFootprint\Domain\Model\AttendeeSurveyResponse\AttendeeSurveyResponseRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminEventAttendeeSurveysExportType;
use App\DataExchange\Domain\Model\Exportation\ExportationType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\EventOperations\Application\Event\EventAuthorizer;
use App\EventOperations\Domain\Model\Event\Event;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class EventAttendeeSurveysExportationType implements ExportationType
{
    public function __construct(
        private readonly AttendeeSurveyResponseRepository $attendeeSurveyResponseRepository,
        private readonly AttendeeSurveyRepository $attendeeSurveyRepository,
        private readonly OriginCompensationsCalculator $originCompensationsCalculator,
        private readonly EventAuthorizer $authorizer,
        private readonly TranslatorInterface $translator,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
        private readonly RequestStack $requestStack,
    ) {}

    public function code(): string
    {
        return 'event_attendee_surveys';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Encuestas de asistentes');
    }

    public function returnRoute(): string
    {
        return 'events';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('registeredAt', new TranslatableMessage('Fecha')),
            new ExportColumnDefinition('origin', new TranslatableMessage('Origen')),
            new ExportColumnDefinition('cameFromAnotherCity', new TranslatableMessage('De otra ciudad')),
            new ExportColumnDefinition('usedHotel', new TranslatableMessage('¿Se alojó?')),
            new ExportColumnDefinition('accommodation', new TranslatableMessage('Alojamiento')),
            new ExportColumnDefinition('hotelNights', new TranslatableMessage('Noches')),
            new ExportColumnDefinition('hotelPeople', new TranslatableMessage('Personas alojadas')),
            new ExportColumnDefinition('overnightStays', new TranslatableMessage('Pernoctaciones')),
            new ExportColumnDefinition('intercityRoundTrip', new TranslatableMessage('Ida y vuelta intercity')),
            new ExportColumnDefinition('intercityTrips', new TranslatableMessage('Viajes intercity')),
            new ExportColumnDefinition('movedWithinCity', new TranslatableMessage('Movilidad urbana')),
            new ExportColumnDefinition('cityRoundTrip', new TranslatableMessage('Ida y vuelta urbana')),
            new ExportColumnDefinition('cityTrips', new TranslatableMessage('Viajes urbanos')),
            new ExportColumnDefinition('mobilityEmissions', new TranslatableMessage('Emisiones movilidad (kg CO₂eq)')),
            new ExportColumnDefinition('overnightEmissions', new TranslatableMessage('Emisiones pernoctación (kg CO₂eq)')),
            new ExportColumnDefinition('totalEmissions', new TranslatableMessage('Emisiones totales (kg CO₂eq)')),
            new ExportColumnDefinition('compensated', new TranslatableMessage('Compensado')),
            new ExportColumnDefinition('project', new TranslatableMessage('Proyecto')),
            new ExportColumnDefinition('contributionAmount', new TranslatableMessage('Aportación (€)')),
            new ExportColumnDefinition('compensatedEmissions', new TranslatableMessage('Emisiones compensadas (kg CO₂eq)')),
        ];
    }

    public function filterFormClass(): string
    {
        return AdminEventAttendeeSurveysExportType::class;
    }

    public function createFilter(): object
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        if (null === $query) {
            return new EventAttendeeSurveysExportFilter();
        }

        $eventId = $this->string($query->get('eventId'));

        return new EventAttendeeSurveysExportFilter(
            eventId: null === $eventId ? null : new EventId($eventId),
            from: $this->date($this->string($query->get('from'))),
            to: $this->date($this->string($query->get('to'))),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof EventAttendeeSurveysExportFilter) {
            return [];
        }

        return [
            'eventId' => $filter->eventId instanceof EventId ? (string) $filter->eventId : null,
            'from' => $filter->from?->format('Y-m-d'),
            'to' => $filter->to?->format('Y-m-d'),
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $eventId = $this->string($criteria['eventId'] ?? null);

        return new EventAttendeeSurveysExportFilter(
            eventId: null === $eventId ? null : new EventId($eventId),
            from: $this->date($this->string($criteria['from'] ?? null)),
            to: $this->date($this->string($criteria['to'] ?? null)),
        );
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof EventAttendeeSurveysExportFilter) {
            return;
        }

        if (!$filter->eventId instanceof EventId) {
            return;
        }

        $survey = $this->attendeeSurveyRepository->findByEventId($filter->eventId);

        if (null === $survey) {
            return;
        }

        $to = $filter->to?->setTime(23, 59, 59);

        $responses = array_values(array_filter(
            $this->attendeeSurveyResponseRepository->findByAttendeeSurveyId($survey->id()),
            static function (AttendeeSurveyResponse $response) use ($filter, $to): bool {
                $registeredAt = $response->registeredAt();

                if ($filter->from instanceof \DateTimeImmutable && $registeredAt < $filter->from) {
                    return false;
                }

                return !($to instanceof \DateTimeImmutable && $registeredAt > $to);
            },
        ));

        $compensations = $this->originCompensationsCalculator->calculate(
            ProjectContributionOrigin::AttendeeSurveyResponse,
            array_map(static fn(AttendeeSurveyResponse $response): string => (string) $response->id(), $responses),
        );

        foreach ($responses as $response) {
            $compensation = $compensations[(string) $response->id()] ?? null;

            yield [
                'registeredAt' => $response->registeredAt()->format('d/m/Y H:i'),
                'origin' => $response->originLabel() ?? '',
                'cameFromAnotherCity' => $this->yesNo($response->cameFromAnotherCity()),
                'usedHotel' => $this->yesNo($response->usedHotel()),
                'accommodation' => $response->accommodationLabel() ?? '',
                'hotelNights' => $response->hotelNights() ?? '',
                'hotelPeople' => $response->hotelPeople() ?? '',
                'overnightStays' => $response->overnightStays(),
                'intercityRoundTrip' => $this->yesNo($response->intercityRoundTrip()),
                'intercityTrips' => $response->intercityTrips() ?? '',
                'movedWithinCity' => $this->yesNo($response->movedWithinCity()),
                'cityRoundTrip' => $this->yesNo($response->cityRoundTrip()),
                'cityTrips' => $response->cityTrips() ?? '',
                'mobilityEmissions' => $response->mobilityEmissions()->co2eq(),
                'overnightEmissions' => $response->overnightEmissions()->co2eq(),
                'totalEmissions' => $response->emissions()->co2eq(),
                'compensated' => $this->yesNo($compensation instanceof OriginCompensation),
                'project' => null === $compensation ? '' : $compensation->projects,
                'contributionAmount' => null === $compensation ? '' : round($compensation->amount, 2),
                'compensatedEmissions' => null === $compensation ? '' : round($compensation->co2eqKg, 3),
            ];
        }
    }

    public function isAuthorized(User $user): bool
    {
        $subject = new Event(
            id: EventId::generate(),
            accountId: $this->currentAccountIdProvider->currentAccountId(),
        );

        return $this->authorizer->canDoThat($subject, Action::Export, $user);
    }

    private function yesNo(bool $value): string
    {
        return $value ? $this->translator->trans('Sí') : $this->translator->trans('No');
    }

    private function string(mixed $value): ?string
    {
        if (!is_string($value) || '' === trim($value)) {
            return null;
        }

        return trim($value);
    }

    private function date(?string $value): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return false === $date ? null : $date;
    }
}
