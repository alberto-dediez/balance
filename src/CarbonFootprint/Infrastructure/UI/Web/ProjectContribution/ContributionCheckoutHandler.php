<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution;

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
use App\Balance\Domain\Model\ProjectContribution\ProjectNotContributableException;
use App\Payments\Domain\Model\Payment\PaymentGatewayException;
use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;

final class ContributionCheckoutHandler
{
    public function __construct(
        private QuoteContributionService $quoteContribution,
        private RegisterProjectContributionService $registerProjectContribution,
        private ProjectRepository $projectRepository,
        private CountryRepository $countryRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /** @param array<string, mixed> $defaults */
    public function handle(
        ContributionContext $context,
        ContributionRoutes $routes,
        string $projectId,
        Request $request,
        array $defaults = [],
    ): ContributionCheckoutResult {
        $contribution = $request->isMethod('POST') ? $request->request->all('contribution') : $defaults;
        $countries = $this->countries();
        $country = $this->country($contribution, $countries);
        $freeAmount = $this->freeAmount($contribution);

        $amountError = $this->isFreeMode($contribution)
            && null === $freeAmount
            && '' !== $this->amountTyped($contribution);

        $offersEmissions = $context->co2eqKg > 0.0;
        $canQuote = null !== $country && ($offersEmissions || null !== $freeAmount);

        try {
            $quote = $canQuote
                ? ($this->quoteContribution)(new QuoteContributionRequest(
                    accountId: $context->accountId,
                    projectId: new ProjectId($projectId),
                    co2eqKg: null === $freeAmount ? $context->co2eqKg : null,
                    freeAmount: $freeAmount,
                    contributorCountry: (string) $country,
                    contributorIsCompany: $this->isCompany($contribution),
                    contributorVatNumber: $this->nullableString($contribution['vatNumber'] ?? null),
                ))
                : null;
        } catch (ProjectNotContributableException|\InvalidArgumentException) {
            return ContributionCheckoutResult::invalid();
        }

        $project = null !== $quote ? $quote->project : $this->projectRepository->find(new ProjectId($projectId));

        if (null === $project) {
            return ContributionCheckoutResult::invalid();
        }

        $exceedsCredit = null !== $quote
            && $quote->limitedByCredit
            && $quote->amounts->tonnes > $quote->availableTonnes;

        $belowMinimum = null !== $quote && $quote->amounts->totalAmount < ContributionCalculator::MIN_TOTAL_AMOUNT;

        if (null !== $quote && !$exceedsCredit && !$belowMinimum && $request->isMethod('POST') && isset($contribution['pay'])) {
            $redirect = $this->register($context, $routes, $projectId, $freeAmount, $country, $contribution, $request);

            if (null !== $redirect) {
                return ContributionCheckoutResult::redirect($redirect);
            }
        }

        return ContributionCheckoutResult::render([
            'token' => $context->token,
            'co2eqKg' => $context->co2eqKg,
            'routes' => $routes,
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
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function countries(): array
    {
        $countries = [];

        foreach ($this->countryRepository->findByFilter(new CountryFilter(limit: 0)) as $country) {
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

    private function maxFreeAmount(?QuoteContributionResponse $quote): ?float
    {
        if (!$quote instanceof QuoteContributionResponse || !$quote->limitedByCredit || is_infinite($quote->availableTonnes)) {
            return null;
        }

        $maxTaxableAmount = $quote->availableTonnes * $quote->amounts->pricePerTonne;

        return floor($maxTaxableAmount * (1 + $quote->amounts->vatRate / 100) * 100) / 100;
    }

    /** @param array<string, mixed> $contribution */
    private function register(
        ContributionContext $context,
        ContributionRoutes $routes,
        string $projectId,
        ?float $freeAmount,
        ?string $country,
        array $contribution,
        Request $request,
    ): ?string {
        $name = trim((string) ($contribution['name'] ?? ''));
        $email = trim((string) ($contribution['email'] ?? ''));

        if ('' === $name || '' === $email || null === $country) {
            $this->addFlash($request, 'danger', new TranslatableMessage('Necesitamos tu nombre, tu email y tu país para poder emitir el recibo.'));

            return null;
        }

        $contributionId = ProjectContributionId::generate();

        try {
            $registered = ($this->registerProjectContribution)(new RegisterProjectContributionRequest(
                projectContributionId: $contributionId,
                accountId: $context->accountId,
                projectId: new ProjectId($projectId),
                contributorName: $name,
                contributorEmail: $email,
                contributorCountry: $country,
                successUrl: $this->urlGenerator->generate(
                    $routes->return,
                    $routes->contributionParams((string) $contributionId),
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
                cancelUrl: $this->urlGenerator->generate(
                    $routes->cancel,
                    $routes->contributionParams((string) $contributionId),
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
                origin: $context->origin,
                originId: $context->originId,
                originName: $context->originName,
                co2eqKg: null === $freeAmount ? $context->co2eqKg : null,
                freeAmount: $freeAmount,
                contributorIsCompany: $this->isCompany($contribution),
                contributorCompanyName: $this->nullableString($contribution['companyName'] ?? null),
                contributorVatNumber: $this->nullableString($contribution['vatNumber'] ?? null),
                locale: LocaleGetter::locale(),
            ));
        } catch (InsufficientProjectCreditException) {
            $this->addFlash($request, 'danger', new TranslatableMessage('Este proyecto ya no tiene crédito suficiente para compensar tu huella.'));

            return null;
        } catch (ContributionAmountTooLowException) {
            $this->addFlash($request, 'danger', new TranslatableMessage(
                'El importe mínimo de una aportación es {min} €.',
                ['{min}' => number_format(ContributionCalculator::MIN_TOTAL_AMOUNT, 2, ',', '.')],
            ));

            return null;
        } catch (ProjectNotContributableException|\InvalidArgumentException $e) {
            $this->addFlash($request, 'danger', new TranslatableMessage('No hemos podido preparar tu aportación: {error}', ['{error}' => $e->getMessage()]));

            return null;
        } catch (PaymentGatewayException) {
            $this->addFlash($request, 'danger', new TranslatableMessage('No hemos podido conectar con la pasarela de pago. Inténtalo de nuevo en unos minutos.'));

            return null;
        }

        return $registered->redirectUrl;
    }

    private function addFlash(Request $request, string $type, TranslatableMessage $message): void
    {
        $session = $request->getSession();

        if ($session instanceof Session) {
            $session->getFlashBag()->add($type, $message);
        }
    }

    /** @param array<string, mixed> $contribution */
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
