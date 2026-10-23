<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\Contribution\OriginCompensation;
use App\CarbonFootprint\Application\Contribution\OriginCompensationsCalculator;
use App\CarbonFootprint\Application\TripCalculation\Export\AccountTripCalculationsExportFilter;
use App\CarbonFootprint\Application\TripCalculationLink\TripCalculationLinkAuthorizer;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkId;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AccountTripCalculationsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\DataExchange\Domain\Model\Exportation\FilterAuthorizedExportationType;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class AccountTripCalculationsExportationType implements FilterAuthorizedExportationType
{
    public function __construct(
        private readonly TripCalculationRepository $tripCalculationRepository,
        private readonly TripCalculationLinkRepository $tripCalculationLinkRepository,
        private readonly OriginCompensationsCalculator $originCompensationsCalculator,
        private readonly TripCalculationLinkAuthorizer $authorizer,
        private readonly TranslatorInterface $translator,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
        private readonly RequestStack $requestStack,
    ) {}

    public function code(): string
    {
        return 'account_trip_calculations';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Cálculos de viajes');
    }

    public function returnRoute(): string
    {
        return 'carbon_footprint_trip_calculator_responses';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('registeredAt', new TranslatableMessage('Fecha')),
            new ExportColumnDefinition('origin', new TranslatableMessage('Origen')),
            new ExportColumnDefinition('destination', new TranslatableMessage('Destino')),
            new ExportColumnDefinition('roundTrip', new TranslatableMessage('Ida y vuelta')),
            new ExportColumnDefinition('trips', new TranslatableMessage('Viajes')),
            new ExportColumnDefinition('nights', new TranslatableMessage('Noches')),
            new ExportColumnDefinition('people', new TranslatableMessage('Personas alojadas')),
            new ExportColumnDefinition('overnightStays', new TranslatableMessage('Pernoctaciones')),
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
        return AccountTripCalculationsExportType::class;
    }

    public function createFilter(): object
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        return new AccountTripCalculationsExportFilter(
            accountId: $this->currentAccountIdProvider->currentAccountId(),
            from: $this->date($this->string($query?->get('from'))),
            to: $this->date($this->string($query?->get('to'))),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof AccountTripCalculationsExportFilter) {
            return [];
        }

        return [
            'accountId' => $filter->accountId instanceof AccountId ? (string) $filter->accountId : null,
            'from' => $filter->from?->format('Y-m-d'),
            'to' => $filter->to?->format('Y-m-d'),
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $accountId = $this->string($criteria['accountId'] ?? null);

        return new AccountTripCalculationsExportFilter(
            accountId: null === $accountId ? null : new AccountId($accountId),
            from: $this->date($this->string($criteria['from'] ?? null)),
            to: $this->date($this->string($criteria['to'] ?? null)),
        );
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof AccountTripCalculationsExportFilter || !$filter->accountId instanceof AccountId) {
            return;
        }

        $link = $this->tripCalculationLinkRepository->findForAccount($filter->accountId);

        if (null === $link) {
            return;
        }

        $calculationFilter = new TripCalculationFilter(
            tripCalculationLinkId: $link->id(),
            from: $filter->from,
            to: $filter->to?->setTime(23, 59, 59),
            limit: 0,
        );

        $calculationFilter->orderWay = 'ASC';

        $tripCalculations = $this->tripCalculationRepository->findByFilter($calculationFilter);

        $compensations = $this->originCompensationsCalculator->calculate(
            ProjectContributionOrigin::TripCalculation,
            array_map(static fn(TripCalculation $tripCalculation): string => (string) $tripCalculation->id(), $tripCalculations),
        );

        foreach ($tripCalculations as $tripCalculation) {
            $compensation = $compensations[(string) $tripCalculation->id()] ?? null;

            yield [
                'registeredAt' => $tripCalculation->registeredAt()->format('d/m/Y H:i'),
                'origin' => $tripCalculation->originLabel() ?? '',
                'destination' => $tripCalculation->destinationLabel() ?? '',
                'roundTrip' => $this->yesNo($tripCalculation->roundTrip()),
                'trips' => $tripCalculation->trips() ?? '',
                'nights' => $tripCalculation->nights() ?? '',
                'people' => $tripCalculation->people() ?? '',
                'overnightStays' => $tripCalculation->overnightStays(),
                'mobilityEmissions' => $tripCalculation->mobilityEmissions()->co2eq(),
                'overnightEmissions' => $tripCalculation->overnightEmissions()->co2eq(),
                'totalEmissions' => $tripCalculation->emissions()->co2eq(),
                'compensated' => $this->yesNo($compensation instanceof OriginCompensation),
                'project' => null === $compensation ? '' : $compensation->projects,
                'contributionAmount' => null === $compensation ? '' : round($compensation->amount, 2),
                'compensatedEmissions' => null === $compensation ? '' : round($compensation->co2eqKg, 3),
            ];
        }
    }

    public function isAuthorized(User $user): bool
    {
        return $this->authorizer->canDoThat(
            $this->linkOf($this->currentAccountIdProvider->currentAccountId()),
            Action::Export,
            $user,
        );
    }

    public function isAuthorizedForFilter(object $filter, User $user): bool
    {
        if (!$filter instanceof AccountTripCalculationsExportFilter || !$filter->accountId instanceof AccountId) {
            return false;
        }

        return $this->authorizer->canDoThat($this->linkOf($filter->accountId), Action::Export, $user);
    }

    private function linkOf(?AccountId $accountId): TripCalculationLink
    {
        $link = null !== $accountId ? $this->tripCalculationLinkRepository->findForAccount($accountId) : null;

        return $link ?? new TripCalculationLink(id: TripCalculationLinkId::generate(), accountId: $accountId);
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
