<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\DataExchange;

use App\Balance\Domain\Model\ProjectContribution\ProjectContributionOrigin;
use App\CarbonFootprint\Application\Contribution\OriginCompensation;
use App\CarbonFootprint\Application\Contribution\OriginCompensationsCalculator;
use App\CarbonFootprint\Application\TripCalculation\Export\PropertyTripCalculationsExportFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculation;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationFilter;
use App\CarbonFootprint\Domain\Model\TripCalculation\TripCalculationRepository;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\AdminPropertyTripCalculationsExportType;
use App\DataExchange\Domain\Model\Exportation\ExportColumnDefinition;
use App\DataExchange\Domain\Model\Exportation\FilterAuthorizedExportationType;
use App\Property\Application\Property\PropertyAuthorizer;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

class PropertyTripCalculationsExportationType implements FilterAuthorizedExportationType
{
    public function __construct(
        private readonly TripCalculationRepository $tripCalculationRepository,
        private readonly TripCalculationLinkRepository $tripCalculationLinkRepository,
        private readonly OriginCompensationsCalculator $originCompensationsCalculator,
        private readonly PropertyAuthorizer $authorizer,
        private readonly PropertyRepository $propertyRepository,
        private readonly TranslatorInterface $translator,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
        private readonly RequestStack $requestStack,
    ) {}

    public function code(): string
    {
        return 'property_trip_calculations';
    }

    public function name(): string|TranslatableMessage
    {
        return new TranslatableMessage('Cálculos de propiedad');
    }

    public function returnRoute(): string
    {
        return 'properties';
    }

    public function columnDefinitions(): array
    {
        return [
            new ExportColumnDefinition('registeredAt', new TranslatableMessage('Fecha')),
            new ExportColumnDefinition('origin', new TranslatableMessage('Origen')),
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
        return AdminPropertyTripCalculationsExportType::class;
    }

    public function createFilter(): object
    {
        $query = $this->requestStack->getCurrentRequest()?->query;

        if (null === $query) {
            return new PropertyTripCalculationsExportFilter();
        }

        $propertyId = $this->string($query->get('propertyId'));

        return new PropertyTripCalculationsExportFilter(
            propertyId: null === $propertyId ? null : new PropertyId($propertyId),
            from: $this->date($this->string($query->get('from'))),
            to: $this->date($this->string($query->get('to'))),
        );
    }

    public function dehydrateFilter(object $filter): array
    {
        if (!$filter instanceof PropertyTripCalculationsExportFilter) {
            return [];
        }

        return [
            'propertyId' => $filter->propertyId instanceof PropertyId ? (string) $filter->propertyId : null,
            'from' => $filter->from?->format('Y-m-d'),
            'to' => $filter->to?->format('Y-m-d'),
        ];
    }

    public function hydrateFilter(array $criteria): object
    {
        $propertyId = $this->string($criteria['propertyId'] ?? null);

        return new PropertyTripCalculationsExportFilter(
            propertyId: null === $propertyId ? null : new PropertyId($propertyId),
            from: $this->date($this->string($criteria['from'] ?? null)),
            to: $this->date($this->string($criteria['to'] ?? null)),
        );
    }

    public function rows(object $filter, array $selectedColumnKeys): iterable
    {
        if (!$filter instanceof PropertyTripCalculationsExportFilter) {
            return;
        }

        if (!$filter->propertyId instanceof PropertyId) {
            return;
        }

        $link = $this->tripCalculationLinkRepository->findByPropertyId($filter->propertyId);

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
            ProjectContributionOrigin::PropertyTripCalculation,
            array_map(static fn(TripCalculation $tripCalculation): string => (string) $tripCalculation->id(), $tripCalculations),
        );

        foreach ($tripCalculations as $tripCalculation) {
            $compensation = $compensations[(string) $tripCalculation->id()] ?? null;

            yield [
                'registeredAt' => $tripCalculation->registeredAt()->format('d/m/Y H:i'),
                'origin' => $tripCalculation->originLabel() ?? '',
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
        $subject = new Property(
            id: PropertyId::generate(),
            accountId: $this->currentAccountIdProvider->currentAccountId(),
        );

        return $this->authorizer->canDoThat($subject, Action::Export, $user);
    }

    public function isAuthorizedForFilter(object $filter, User $user): bool
    {
        if (!$filter instanceof PropertyTripCalculationsExportFilter || !$filter->propertyId instanceof PropertyId) {
            return false;
        }

        $property = $this->propertyRepository->find($filter->propertyId);

        if (!$property instanceof Property) {
            return false;
        }

        return $this->authorizer->canDoThat($property, Action::Export, $user);
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
