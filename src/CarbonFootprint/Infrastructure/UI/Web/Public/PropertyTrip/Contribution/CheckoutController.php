<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\Contribution;

use App\Balance\Application\ProjectContribution\Quote\QuoteContributionRequest;
use App\Balance\Application\ProjectContribution\Quote\QuoteContributionResponse;
use App\Balance\Application\ProjectContribution\Quote\QuoteContributionService;
use App\Balance\Application\ProjectContribution\Register\RegisterProjectContributionRequest;
use App\Balance\Application\ProjectContribution\Register\RegisterProjectContributionService;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\ContributionAmountTooLowException;
use App\Balance\Domain\Model\ProjectContribution\ContributionCalculator;
use App\Balance\Domain\Model\ProjectContribution\InsufficientProjectCreditException;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\Balance\Domain\Model\ProjectContribution\ProjectNotContributableException;
use App\CarbonFootprint\Application\TripCalculationLink\GetByToken\GetTripCalculationLinkByTokenService;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PropertyTrip\PropertyTripPublicControllerTrait;
use App\CarbonFootprint\Infrastructure\UI\Web\Public\PublicFormRateLimiter;
use App\Payments\Domain\Model\Payment\PaymentGatewayException;
use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Domain\Model\Account\AccountId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[Route(
    '/carbon-footprint/property-trip/{token}/result/{tripCalculationId}/compensate/{projectId}',
    name: 'public_carbon_footprint_property_trip_contribution_checkout',
    methods: ['GET', 'POST'],
)]
class CheckoutController extends AbstractController
{
    use PropertyTripPublicControllerTrait;
    use PropertyTripContributionTrait;

    public function __construct(
        private PublicFormRateLimiter $propertyTripRateLimiter,
    ) {}

    public function __invoke(
        string $token,
        string $tripCalculationId,
        string $projectId,
        GetTripCalculationLinkByTokenService $getByToken,
        TripCalculationRepository $tripCalculationRepository,
        QuoteContributionService $quoteContribution,
        RegisterProjectContributionService $registerProjectContribution,
        ProjectRepository $projectRepository,
        CountryRepository $countryRepository,
        Request $request,
    ): Response {
        if ($limited = $this->propertyTripRateLimiter->check($request)) {
            return $limited;
        }

        $link = $this->resolveLink($token, $getByToken);

        if (null === $link) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $tripCalculation = $this->resolveTripCalculation($tripCalculationId, $link, $tripCalculationRepository);
        $accountId = $this->accountIdOf($link);

        if (null === $tripCalculation || !$accountId instanceof AccountId) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $co2eqKg = (float) $tripCalculation->emissions()->co2eq();
        $contribution = $request->request->all('contribution');
        $countries = $this->countries($countryRepository);
        $country = $this->country($contribution, $countries);
        $freeAmount = $this->freeAmount($contribution);

        $amountError = $this->isFreeMode($contribution)
            && null === $freeAmount
            && '' !== $this->amountTyped($contribution);

        // A local visitor has nothing to offset, so only a free amount can be quoted — and until they
        // type one there is no breakdown to show yet. The country decides the VAT, so without it the
        // breakdown would be a figure the guest is not going to be charged.
        $offersEmissions = $co2eqKg > 0.0;
        $canQuote = null !== $country && ($offersEmissions || null !== $freeAmount);

        try {
            $quote = $canQuote
                ? $quoteContribution(new QuoteContributionRequest(
                    accountId: $accountId,
                    projectId: new ProjectId($projectId),
                    co2eqKg: null === $freeAmount ? $co2eqKg : null,
                    freeAmount: $freeAmount,
                    contributorCountry: (string) $country,
                    contributorIsCompany: $this->isCompany($contribution),
                    contributorVatNumber: $this->nullableString($contribution['vatNumber'] ?? null),
                ))
                : null;
        } catch (ProjectNotContributableException|\InvalidArgumentException) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        $project = null !== $quote ? $quote->project : $projectRepository->find(new ProjectId($projectId));

        if (null === $project) {
            return $this->propertyTripInvalidResponse($this->propertyTripRateLimiter, $request);
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('property_trip_' . $token, $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Token CSRF no válido');
            }
        }

        // The free amount is only known here, so this is the earliest point where it can be checked
        // against the credit left: without this the guest would see a valid breakdown and only find
        // out when paying.
        $exceedsCredit = null !== $quote
            && $quote->limitedByCredit
            && $quote->amounts->tonnes > $quote->availableTonnes;

        // The gateway refuses a charge under half a euro, so the breakdown says it here instead of
        // letting the guest hit a payment error they cannot do anything about.
        $belowMinimum = null !== $quote && $quote->amounts->totalAmount < ContributionCalculator::MIN_TOTAL_AMOUNT;

        // Every other submit only re-renders the breakdown; the payment starts on the explicit pay submit.
        if (null !== $quote && !$exceedsCredit && !$belowMinimum && $request->isMethod('POST') && isset($contribution['pay'])) {
            $redirect = $this->register(
                $registerProjectContribution,
                $accountId,
                $projectId,
                $token,
                $tripCalculationId,
                $co2eqKg,
                $freeAmount,
                $country,
                $contribution,
            );

            if (null !== $redirect) {
                return $this->redirect($redirect);
            }
        }

        $parameters = [
            'token' => $token,
            'property' => $link->property,
            'tripCalculation' => $tripCalculation,
            'tripCalculationId' => $tripCalculationId,
            'projectId' => $projectId,
            'quote' => $quote,
            'project' => $project,
            'offersEmissions' => $offersEmissions,
            'contribution' => $contribution,
            'country' => $country,
            'countries' => $countries,
            'exceedsCredit' => $exceedsCredit,
            'belowMinimum' => $belowMinimum,
            'amountError' => $amountError,
            'maxAmount' => ContributionCalculator::MAX_FREE_AMOUNT,
            'minAmount' => ContributionCalculator::MIN_TOTAL_AMOUNT,
            'maxFreeAmount' => $this->maxFreeAmount($quote),
        ];

        // The guest changing an amount or their tax data only needs the breakdown back, so the page
        // around it is neither rendered nor replaced: what they were typing stays untouched.
        if ($request->isXmlHttpRequest()) {
            return $this->render('@carbon_footprint/public/property_trip/contribution/_summary.html.twig', $parameters);
        }

        return $this->render('@carbon_footprint/public/property_trip/contribution/checkout.html.twig', $parameters);
    }

    /**
     * The guest can only pick from the country catalogue, so the ISO code that ends up in the
     * invoice — and decides the VAT — is always one the application knows.
     *
     * @return array<string, string>
     */
    private function countries(CountryRepository $countryRepository): array
    {
        $countries = [];

        foreach ($countryRepository->findByFilter(new CountryFilter(limit: 0)) as $country) {
            $iso = strtoupper(trim((string) $country->iso()));

            if ('' !== $iso) {
                $countries[$iso] = $country->name();
            }
        }

        asort($countries);

        return $countries;
    }

    /**
     * @param array<string, mixed>  $contribution
     * @param array<string, string> $countries
     */
    private function country(array $contribution, array $countries): ?string
    {
        $country = strtoupper(trim((string) ($contribution['country'] ?? '')));

        return isset($countries[$country]) ? $country : null;
    }

    /**
     * Largest free amount the credit left still backs, rounded down so the amount the guest types
     * never lands above the availability once the breakdown rounds it to cents.
     */
    private function maxFreeAmount(?QuoteContributionResponse $quote): ?float
    {
        if (!$quote instanceof QuoteContributionResponse || !$quote->limitedByCredit || is_infinite($quote->availableTonnes)) {
            return null;
        }

        return floor($quote->availableTonnes * $quote->amounts->pricePerTonne * 100) / 100;
    }

    /** @param array<string, mixed> $contribution */
    private function register(
        RegisterProjectContributionService $registerProjectContribution,
        AccountId $accountId,
        string $projectId,
        string $token,
        string $tripCalculationId,
        float $co2eqKg,
        ?float $freeAmount,
        ?string $country,
        array $contribution,
    ): ?string {
        $name = trim((string) ($contribution['name'] ?? ''));
        $email = trim((string) ($contribution['email'] ?? ''));

        if ('' === $name || '' === $email || null === $country) {
            $this->addFlash('danger', new TranslatableMessage('Necesitamos tu nombre, tu email y tu país para poder emitir el recibo.'));

            return null;
        }

        $contributionId = ProjectContributionId::generate();

        try {
            $registered = $registerProjectContribution(new RegisterProjectContributionRequest(
                projectContributionId: $contributionId,
                accountId: $accountId,
                projectId: new ProjectId($projectId),
                contributorName: $name,
                contributorEmail: $email,
                contributorCountry: $country,
                successUrl: $this->generateUrl(
                    'public_carbon_footprint_property_trip_contribution_return',
                    ['token' => $token, 'contributionId' => (string) $contributionId],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
                cancelUrl: $this->generateUrl(
                    'public_carbon_footprint_property_trip_contribution_cancel',
                    ['token' => $token, 'contributionId' => (string) $contributionId],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
                origin: ProjectContributionOrigin::PropertyTripCalculation,
                originId: $tripCalculationId,
                co2eqKg: null === $freeAmount ? $co2eqKg : null,
                freeAmount: $freeAmount,
                contributorIsCompany: $this->isCompany($contribution),
                contributorCompanyName: $this->nullableString($contribution['companyName'] ?? null),
                contributorVatNumber: $this->nullableString($contribution['vatNumber'] ?? null),
            ));
        } catch (InsufficientProjectCreditException) {
            $this->addFlash('danger', new TranslatableMessage('Este proyecto ya no tiene crédito suficiente para compensar tu huella.'));

            return null;
        } catch (ContributionAmountTooLowException) {
            $this->addFlash('danger', new TranslatableMessage(
                'El importe mínimo de una aportación es {min} €.',
                ['{min}' => number_format(ContributionCalculator::MIN_TOTAL_AMOUNT, 2, ',', '.')],
            ));

            return null;
        } catch (ProjectNotContributableException|\InvalidArgumentException $e) {
            $this->addFlash('danger', new TranslatableMessage('No hemos podido preparar tu aportación: {error}', ['{error}' => $e->getMessage()]));

            return null;
        } catch (PaymentGatewayException) {
            $this->addFlash('danger', new TranslatableMessage('No hemos podido conectar con la pasarela de pago. Inténtalo de nuevo en unos minutos.'));

            return null;
        }

        return $registered->redirectUrl;
    }

    /**
     * Anything the guest types that is not a plain amount within range is discarded here: the
     * gateway would refuse it later, and notations such as "1e9" do reach this point as numeric.
     *
     * @param array<string, mixed> $contribution
     */
    private function freeAmount(array $contribution): ?float
    {
        if (!$this->isFreeMode($contribution)) {
            return null;
        }

        $amount = str_replace(',', '.', $this->amountTyped($contribution));

        if (!is_numeric($amount)) {
            return null;
        }

        $amount = round((float) $amount, 2);

        return $amount > 0.0 && $amount <= ContributionCalculator::MAX_FREE_AMOUNT ? $amount : null;
    }

    /** @param array<string, mixed> $contribution */
    private function isFreeMode(array $contribution): bool
    {
        return 'free' === ($contribution['mode'] ?? 'emissions');
    }

    /** @param array<string, mixed> $contribution */
    private function amountTyped(array $contribution): string
    {
        return trim((string) ($contribution['amount'] ?? ''));
    }

    /** @param array<string, mixed> $contribution */
    private function isCompany(array $contribution): bool
    {
        return (bool) ($contribution['isCompany'] ?? false);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
