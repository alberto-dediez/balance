<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\Contest;

use App\CarbonFootprint\Application\Contest\GetActiveForHost\GetActiveContestForHostRequest;
use App\CarbonFootprint\Application\Contest\GetActiveForHost\GetActiveContestForHostService;
use App\CarbonFootprint\Application\ContestEntry\CheckEligibility\CheckContestEntryEligibilityRequest;
use App\CarbonFootprint\Application\ContestEntry\CheckEligibility\CheckContestEntryEligibilityService;
use App\CarbonFootprint\Application\ContestEntry\Register\RegisterContestEntryRequest;
use App\CarbonFootprint\Application\ContestEntry\Register\RegisterContestEntryService;
use App\CarbonFootprint\Domain\Model\Contest\Contest;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostReference;
use App\CarbonFootprint\Domain\Model\Contest\ContestHostType;
use App\CarbonFootprint\Domain\Model\Contest\ContestNotAcceptingEntriesException;
use App\CarbonFootprint\Domain\Model\Contest\ContestRepository;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntry;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryCountryNotAllowedException;
use App\CarbonFootprint\Domain\Model\ContestEntry\ContestEntryRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterContestEntryType;
use App\CarbonFootprint\Infrastructure\UI\Web\Contest\ContestCountries;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionContext;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionRoutes;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\ConsentRequestReader;
use App\EventOperations\Domain\Model\Event\EventId;
use App\Shared\Domain\Event\DomainEventPublisher;
use App\Shared\Domain\Model\Consent\ConsentProof;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ContestPublicHandler
{
    public function __construct(
        private GetActiveContestForHostService $getActiveContestForHost,
        private CheckContestEntryEligibilityService $checkContestEntryEligibility,
        private RegisterContestEntryService $registerContestEntry,
        private ContestRepository $contestRepository,
        private ContestEntryRepository $contestEntryRepository,
        private ContestCountries $contestCountries,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {}

    public static function hostForEvent(EventId $eventId): ContestHostReference
    {
        return new ContestHostReference(ContestHostType::Event, (string) $eventId);
    }

    public static function hostForTripCalculationLink(TripCalculationLink $tripCalculationLink): ContestHostReference
    {
        $propertyId = $tripCalculationLink->propertyId();

        if (null !== $propertyId) {
            return new ContestHostReference(ContestHostType::Property, (string) $propertyId);
        }

        return new ContestHostReference(ContestHostType::TripCalculationLink, (string) $tripCalculationLink->id());
    }

    public function activeContest(ContestHostReference $host): ?Contest
    {
        return ($this->getActiveContestForHost)(new GetActiveContestForHostRequest($host))->contest;
    }

    public function entryFor(Contest $contest, ContributionContext $context): ?ContestEntry
    {
        $entry = $this->contestEntryRepository->findOneByOrigin($contest->id(), $context->origin, $context->originId);

        if (null === $entry || $entry->isPendingPayment()) {
            return null;
        }

        return $entry;
    }

    /** @return array{contest: ?Contest, contestEntry: ?ContestEntry} */
    public function confirmationParameters(string $projectContributionId): array
    {
        foreach ($this->contestEntryRepository->findByProjectContributionId($projectContributionId) as $entry) {
            $contestId = $entry->contestId();

            if ($entry->isPendingPayment() || null === $contestId) {
                continue;
            }

            $contest = $this->contestRepository->find($contestId);

            if ($contest instanceof Contest) {
                return ['contest' => $contest, 'contestEntry' => $entry];
            }
        }

        return ['contest' => null, 'contestEntry' => null];
    }

    /** @return list<string> */
    public function allowedCountryNames(Contest $contest): array
    {
        $countries = $this->contestCountries->countries();

        return array_map(static fn(string $iso): string => $countries[$iso] ?? $iso, $contest->allowedCountryIsos());
    }

    public function prizesExhausted(Contest $contest): bool
    {
        return $contest->hasAwardedEveryPrize($this->contestEntryRepository->countWinners($contest->id()));
    }

    /** @return array<string, string> */
    public function countries(Contest $contest): array
    {
        $countries = $this->contestCountries->countries();
        $allowed = $contest->allowedCountryIsos();

        if ([] === $allowed) {
            return $countries;
        }

        return array_filter($countries, static fn(string $iso): bool => in_array($iso, $allowed, true), \ARRAY_FILTER_USE_KEY);
    }

    public function consentLabel(Contest $contest, ContributionRoutes $routes): string
    {
        $termsUrl = null !== $routes->contestTerms ? $this->urlGenerator->generate($routes->contestTerms, $routes->contributionRouteParams) : '#';

        return $this->translator->trans(
            'He leído y acepto las <a href="{url}" target="_blank" rel="noopener noreferrer">bases del concurso</a> y consiento que {organizer} trate mis datos para gestionar mi participación y contactarme si resulto ganador.',
            [
                '{url}' => htmlspecialchars($termsUrl, \ENT_QUOTES),
                '{organizer}' => htmlspecialchars($contest->organizerName(), \ENT_QUOTES),
            ],
        );
    }

    /** @return array<string, mixed> */
    public function viewParameters(?Contest $contest, ?ContestEntry $entry, ContributionRoutes $routes): array
    {
        return [
            'contest' => $contest,
            'contestEntry' => $entry,
            'contestRoutes' => $routes,
            'contestPrizesExhausted' => $contest instanceof Contest && $this->prizesExhausted($contest),
        ];
    }

    public function handleEntry(Contest $contest, ContestHostReference $host, ContributionContext $context, ContributionRoutes $routes, Request $request): ContestEntryResult
    {
        $resultUrl = $this->urlGenerator->generate($routes->result, $routes->resultRouteParams);

        if (!$contest->participationMode()->isCalculation()) {
            return ContestEntryResult::redirect($resultUrl);
        }

        if ($this->entryFor($contest, $context) instanceof ContestEntry) {
            return ContestEntryResult::redirect($this->thanksUrl($routes));
        }

        $data = new RegisterContestEntryFormData();
        $form = $this->formFactory->create(RegisterContestEntryType::class, $data, [
            'countries' => $this->countries($contest),
            'adults_only' => $contest->adultsOnly(),
            'consent_label' => $this->consentLabel($contest, $routes),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                ($this->registerContestEntry)(new RegisterContestEntryRequest(
                    contestId: $contest->id(),
                    host: $host,
                    origin: $context->origin,
                    originId: $context->originId,
                    projectContributionId: null,
                    name: trim((string) $data->name),
                    email: trim((string) $data->email),
                    phone: $this->nullableString($data->phone),
                    country: (string) $data->country,
                    locale: LocaleGetter::locale(),
                    consentProof: $this->consentProof($contest, $request),
                    consentAccepted: $data->consent,
                    adultConfirmed: $data->adultConfirmed,
                ));

                return ContestEntryResult::redirect($this->thanksUrl($routes));
            } catch (ContestNotAcceptingEntriesException) {
                return ContestEntryResult::redirect($resultUrl);
            } catch (ContestEntryAlreadyExistsException) {
                DomainEventPublisher::instance()->clearPending();

                $existing = $this->contestEntryRepository->findOneByOrigin($contest->id(), $context->origin, $context->originId);

                if (null !== $existing && !$existing->isPendingPayment()) {
                    return ContestEntryResult::redirect($this->thanksUrl($routes));
                }

                $form->get('email')->addError(new FormError($this->translator->trans('Ya hay una inscripción con este email.')));
            } catch (ContestEntryCountryNotAllowedException) {
                $form->get('country')->addError(new FormError($this->translator->trans('Tu país no puede participar en este concurso.')));
            } catch (\InvalidArgumentException $e) {
                $form->addError(new FormError($this->translator->trans($e->getMessage())));
            }
        }

        return ContestEntryResult::render([
            'form' => $form->createView(),
            'contest' => $contest,
            'routes' => $routes,
            'token' => $context->token,
            'contestPrizesExhausted' => $this->prizesExhausted($contest),
        ]);
    }

    public function checkFromCheckout(Contest $contest, ContestHostReference $host, ContributionContext $context, string $email, string $country, bool $adultConfirmed): ?string
    {
        $alpha2 = $this->contestCountries->alpha2($country);

        if (null === $alpha2) {
            return 'Tu país no puede participar en este concurso. Desmarca la casilla para continuar.';
        }

        try {
            ($this->checkContestEntryEligibility)(new CheckContestEntryEligibilityRequest(
                contestId: $contest->id(),
                host: $host,
                origin: $context->origin,
                originId: $context->originId,
                email: $email,
                country: $alpha2,
                consentAccepted: true,
                adultConfirmed: $adultConfirmed,
            ));
        } catch (ContestEntryCountryNotAllowedException) {
            return 'Tu país no puede participar en este concurso. Desmarca la casilla para continuar.';
        } catch (ContestEntryAlreadyExistsException) {
            return 'Ya hay una inscripción con este email.';
        } catch (ContestNotAcceptingEntriesException) {
            return 'El concurso no admite inscripciones.';
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }

        return null;
    }

    public function registerFromCheckout(
        Contest $contest,
        ContestHostReference $host,
        ContributionContext $context,
        string $projectContributionId,
        string $name,
        string $email,
        ?string $phone,
        string $country,
        bool $adultConfirmed,
        Request $request,
    ): void {
        try {
            ($this->registerContestEntry)(new RegisterContestEntryRequest(
                contestId: $contest->id(),
                host: $host,
                origin: $context->origin,
                originId: $context->originId,
                projectContributionId: $projectContributionId,
                name: $name,
                email: $email,
                phone: $this->nullableString($phone),
                country: (string) $this->contestCountries->alpha2($country),
                locale: LocaleGetter::locale(),
                consentProof: $this->consentProof($contest, $request),
                consentAccepted: true,
                adultConfirmed: $adultConfirmed,
            ));
        } catch (\RuntimeException|\InvalidArgumentException) {
            DomainEventPublisher::instance()->clearPending();
        }
    }

    private function consentProof(Contest $contest, Request $request): ConsentProof
    {
        return new ConsentProof(
            consentedAt: new \DateTimeImmutable('NOW'),
            policyVersion: $contest->termsVersion(),
            locale: LocaleGetter::locale()->value,
            ip: ConsentRequestReader::ip($request),
            userAgent: ConsentRequestReader::userAgent($request),
            acceptLanguage: ConsentRequestReader::acceptLanguage($request),
        );
    }

    private function thanksUrl(ContributionRoutes $routes): string
    {
        return $this->urlGenerator->generate((string) $routes->contestThanks, $routes->routeParams);
    }

    private function nullableString(?string $value): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
