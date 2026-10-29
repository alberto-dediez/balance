<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\OwnerContribution;

use App\Balance\Application\ProjectContribution\Compensate\CompensateWithOwnCreditRequest;
use App\Balance\Application\ProjectContribution\Compensate\CompensateWithOwnCreditService;
use App\Balance\Application\ProjectContribution\OwnerContributorDefaults;
use App\Balance\Application\ProjectContribution\OwnerContributorDefaultsResponse;
use App\Balance\Application\ProjectContribution\Quote\QuoteOwnerCompensationRequest;
use App\Balance\Application\ProjectContribution\Quote\QuoteOwnerCompensationService;
use App\Balance\Domain\Model\Project\ProjectId;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\ProjectContribution\InsufficientProjectCreditException;
use App\Balance\Domain\Model\ProjectContribution\ProjectContributionId;
use App\Balance\Domain\Model\ProjectContribution\ProjectNotContributableException;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionCheckoutResult;
use App\CarbonFootprint\Infrastructure\UI\Web\ProjectContribution\ContributionRoutes;
use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;

final class OwnerContributionCheckoutHandler
{
    public function __construct(
        private QuoteOwnerCompensationService $quoteOwnerCompensation,
        private CompensateWithOwnCreditService $compensateWithOwnCredit,
        private OwnerContributorDefaults $ownerContributorDefaults,
        private ProjectRepository $projectRepository,
        private CountryRepository $countryRepository,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    public function handle(
        OwnerContributionContext $context,
        ContributionRoutes $routes,
        string $projectId,
        User $user,
        Request $request,
    ): ContributionCheckoutResult {
        $countries = $this->countries();
        $defaults = ($this->ownerContributorDefaults)($context->accountId, $user);
        $contribution = $this->contribution($request, $context, $defaults, $countries);
        $co2eqKg = $this->co2eqKg($contribution);

        try {
            $quote = null !== $co2eqKg
                ? ($this->quoteOwnerCompensation)(new QuoteOwnerCompensationRequest(
                    accountId: $context->accountId,
                    projectId: new ProjectId($projectId),
                    co2eqKg: $co2eqKg,
                ))
                : null;
        } catch (ProjectNotContributableException|\InvalidArgumentException) {
            return ContributionCheckoutResult::invalid();
        }

        $project = null !== $quote ? $quote->project : $this->projectRepository->find(new ProjectId($projectId));

        if (null === $project) {
            return ContributionCheckoutResult::invalid();
        }

        $amountError = null === $co2eqKg && '' !== $this->amountTyped($contribution);

        if (
            null !== $quote
            && !$quote->exceedsCredit
            && $request->isMethod('POST')
            && isset($contribution['compensate'])
        ) {
            $redirect = $this->compensate($context, $routes, $projectId, $quote->co2eqKg, $contribution, $user, $request);

            if (null !== $redirect) {
                return ContributionCheckoutResult::redirect($redirect);
            }
        }

        return ContributionCheckoutResult::render([
            'routes' => $routes,
            'projectId' => $projectId,
            'project' => $project,
            'quote' => $quote,
            'contribution' => $contribution,
            'countries' => $countries,
            'country' => $contribution['country'],
            'suggestedCo2eqKg' => $context->suggestedCo2eqKg,
            'ownAvailableTonnes' => $quote?->ownAvailableTonnes,
            'exceedsCredit' => $quote->exceedsCredit ?? false,
            'amountError' => $amountError,
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
     * @param array<string, string> $countries
     *
     * @return array<string, mixed>
     */
    private function contribution(
        Request $request,
        OwnerContributionContext $context,
        OwnerContributorDefaultsResponse $defaults,
        array $countries,
    ): array {
        if ($request->isMethod('POST')) {
            $submitted = $request->request->all('contribution');
            $submitted['country'] = $this->country($submitted['country'] ?? null, $countries);

            return $submitted;
        }

        return [
            'co2eqKg' => null !== $context->suggestedCo2eqKg
                ? number_format($context->suggestedCo2eqKg, 2, '.', '')
                : '',
            'name' => $defaults->name,
            'email' => $defaults->email,
            'country' => $this->country($defaults->country, $countries),
            'isCompany' => $defaults->isCompany,
            'companyName' => $defaults->companyName ?? '',
            'vatNumber' => $defaults->vatNumber ?? '',
        ];
    }

    /** @param array<string, string> $countries */
    private function country(mixed $country, array $countries): ?string
    {
        $country = strtoupper(trim((string) $country));

        return isset($countries[$country]) ? $country : null;
    }

    /** @param array<string, mixed> $contribution */
    private function co2eqKg(array $contribution): ?float
    {
        $amount = str_replace(',', '.', $this->amountTyped($contribution));

        if (!is_numeric($amount)) {
            return null;
        }

        $amount = round((float) $amount, 2);

        return $amount > 0.0 ? $amount : null;
    }

    /** @param array<string, mixed> $contribution */
    private function amountTyped(array $contribution): string
    {
        return trim((string) ($contribution['co2eqKg'] ?? ''));
    }

    /** @param array<string, mixed> $contribution */
    private function compensate(
        OwnerContributionContext $context,
        ContributionRoutes $routes,
        string $projectId,
        float $co2eqKg,
        array $contribution,
        User $user,
        Request $request,
    ): ?string {
        $name = trim((string) ($contribution['name'] ?? ''));
        $email = trim((string) ($contribution['email'] ?? ''));
        $country = $contribution['country'] ?? null;

        if ('' === $name || '' === $email || !is_string($country)) {
            $this->addFlash($request, 'danger', new TranslatableMessage('Necesitamos tu nombre, tu email y tu país para poder emitir el certificado.'));

            return null;
        }

        $contributionId = ProjectContributionId::generate();

        try {
            ($this->compensateWithOwnCredit)(new CompensateWithOwnCreditRequest(
                projectContributionId: $contributionId,
                accountId: $context->accountId,
                projectId: new ProjectId($projectId),
                co2eqKg: $co2eqKg,
                origin: $context->origin,
                originId: $context->originId,
                originName: $context->originName,
                beneficiaryName: $name,
                beneficiaryEmail: $email,
                beneficiaryCountry: $country,
                beneficiaryIsCompany: (bool) ($contribution['isCompany'] ?? false),
                beneficiaryCompanyName: $this->nullableString($contribution['companyName'] ?? null),
                beneficiaryVatNumber: $this->nullableString($contribution['vatNumber'] ?? null),
                locale: LocaleGetter::locale(),
            ), $user);
        } catch (InsufficientProjectCreditException) {
            $this->addFlash($request, 'danger', new TranslatableMessage('Ya no tienes crédito propio suficiente en este proyecto para compensar esas emisiones.'));

            return null;
        } catch (UnauthorizedActionException) {
            $this->addFlash($request, 'danger', new TranslatableMessage('No tienes permiso para compensar en nombre de esta cuenta.'));

            return null;
        } catch (ProjectNotContributableException|\InvalidArgumentException $e) {
            $this->addFlash($request, 'danger', new TranslatableMessage('No hemos podido registrar la compensación: {error}', ['{error}' => new TranslatableMessage($e->getMessage())]));

            return null;
        }

        return $this->urlGenerator->generate(
            'owner_contribution_confirmation',
            $routes->contributionParams((string) $contributionId),
        );
    }

    private function addFlash(Request $request, string $type, TranslatableMessage $message): void
    {
        $session = $request->getSession();

        if ($session instanceof Session) {
            $session->getFlashBag()->add($type, $message);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }
}
