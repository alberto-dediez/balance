<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Transport\Import;

use App\CarbonFootprint\Application\Transport\TransportAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Transport\Transport;
use App\CarbonFootprint\Domain\Model\Transport\TransportId;
use App\CarbonFootprint\Domain\Model\Transport\TransportRepository;
use App\CarbonFootprint\Domain\Model\Transport\Vehicle;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportTransportService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private TransportRepository $transportRepository,
        private TransportAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportTransportRequest $request, User $user): ImportTransportResponse
    {
        $year = $this->resolveYear($request->year);

        $transport = $this->resolveTransport($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $transport->setEmissionFactor($this->buildEmissionFactor($request, $transport->unit(), $year, $group));

        $this->transportRepository->add($transport);

        return new ImportTransportResponse(
            transportId: $transport->id(),
        );
    }

    private function resolveTransport(ImportTransportRequest $request, User $user): Transport
    {
        if (!empty($request->transport)) {
            $transport = $this->transportRepository->find(new TransportId($request->transport));

            if (null === $transport) {
                throw new \InvalidArgumentException('Transport not found');
            }

            $this->authorizer->denyUnlessCanDoThat($transport, SecurityAction::Edit, $user);

            return $transport;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('A transport or a name is required');
        }

        return $this->registerTransport($name, $request, $user);
    }

    private function registerTransport(string $name, ImportTransportRequest $request, User $user): Transport
    {
        $id = TransportId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Transport(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $transport = new Transport($id);
        $transport->register(
            $name,
            Unit::tryFrom((string) $request->unit) ?? Unit::Kilogram,
            Vehicle::tryFrom((string) $request->vehicle) ?? Vehicle::PassengerCars,
            $isDefaultLocale ? null : $locale,
        );

        return $transport;
    }

    private function buildEmissionFactor(ImportTransportRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportTransportRequest;
    }
}
