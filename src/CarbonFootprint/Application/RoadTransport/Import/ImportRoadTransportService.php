<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RoadTransport\Import;

use App\CarbonFootprint\Application\RoadTransport\RoadTransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransport;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportId;
use App\CarbonFootprint\Domain\Model\RoadTransport\RoadTransportRepository;
use App\CarbonFootprint\Domain\Model\RoadTransport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportRoadTransportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private RoadTransportRepository $roadTransportRepository,
        private RoadTransportAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportRoadTransportRequest $request, User $user): ImportRoadTransportResponse
    {
        $year = $this->resolveYear($request->year);

        $roadTransport = $this->resolveRoadTransport($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $roadTransport->setEmissionFactor($this->buildEmissionFactor($request, $roadTransport->unit(), $year, $group));

        $this->roadTransportRepository->add($roadTransport);

        return new ImportRoadTransportResponse(
            roadTransportId: $roadTransport->id(),
        );
    }

    private function resolveRoadTransport(ImportRoadTransportRequest $request, User $user): RoadTransport
    {
        if (!empty($request->roadTransport)) {
            $roadTransport = $this->roadTransportRepository->find(new RoadTransportId($request->roadTransport));

            if (null === $roadTransport) {
                throw new \InvalidArgumentException('Road transport not found');
            }

            $this->authorizer->denyUnlessCanDoThat($roadTransport, SecurityAction::Edit, $user);

            return $roadTransport;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('A road transport or a name is required');
        }

        return $this->registerRoadTransport($name, $request, $user);
    }

    private function registerRoadTransport(string $name, ImportRoadTransportRequest $request, User $user): RoadTransport
    {
        $id = RoadTransportId::generate();

        $this->authorizer->denyUnlessCanDoThat(new RoadTransport(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $roadTransport = new RoadTransport($id);
        $roadTransport->register(
            $name,
            Unit::tryFrom((string) $request->unit) ?? Unit::Kilogram,
            Vehicle::tryFrom((string) $request->vehicle) ?? Vehicle::PassengerCars,
            $isDefaultLocale ? null : $locale,
        );

        return $roadTransport;
    }

    private function buildEmissionFactor(ImportRoadTransportRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, $unit, $source);
    }

    private function resolveEmissionFactorGroup(?string $value): EmissionFactorGroup
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('An emission factor group is required');
        }

        $group = $this->emissionFactorGroupRepository->find(new EmissionFactorGroupId($value));

        if (null === $group) {
            throw new \InvalidArgumentException('Emission factor group not found');
        }

        return $group;
    }

    private function resolveYear(?string $year): int
    {
        $parsed = (int) $year;

        if ($parsed <= 0) {
            throw new \InvalidArgumentException('A valid year is required');
        }

        return $parsed;
    }

    private function decimal(?string $value): string
    {
        $value = trim((string) $value);

        return '' === $value ? '0' : str_replace(',', '.', $value);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ImportRoadTransportRequest;
    }
}
