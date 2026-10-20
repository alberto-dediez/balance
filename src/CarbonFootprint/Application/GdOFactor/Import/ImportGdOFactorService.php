<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Import;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ImportGdOFactorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private GdOFactorRepository $gdOFactorRepository,
        private GdOFactorAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportGdOFactorRequest $request, User $user): ImportGdOFactorResponse
    {
        $year = $this->resolveYear($request->year);

        $gdOFactor = $this->resolveGdOFactor($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $gdOFactor->setEmissionFactor(
            $this->buildEmissionFactor($request, $year, $group),
            $this->buildWellToTankEmissionFactor($request, $year, $group),
        );

        $this->gdOFactorRepository->add($gdOFactor);

        return new ImportGdOFactorResponse(
            gdOFactorId: $gdOFactor->id(),
        );
    }

    private function resolveGdOFactor(ImportGdOFactorRequest $request, User $user): GdOFactor
    {
        if (!empty($request->gdOFactor)) {
            $gdOFactor = $this->gdOFactorRepository->find(new GdOFactorId($request->gdOFactor));

            if (null === $gdOFactor) {
                throw new \InvalidArgumentException('GdOFactor not found');
            }

            $this->authorizer->denyUnlessCanDoThat($gdOFactor, SecurityAction::Edit, $user);

            return $gdOFactor;
        }

        $name = trim((string) $request->name);

        if (empty($name)) {
            throw new \InvalidArgumentException('A GdO factor or a name is required');
        }

        return $this->registerGdOFactor($name, $user);
    }

    private function registerGdOFactor(string $name, User $user): GdOFactor
    {
        $id = GdOFactorId::generate();

        $this->authorizer->denyUnlessCanDoThat(new GdOFactor(id: $id), SecurityAction::List, $user);

        $gdOFactor = new GdOFactor($id);
        $gdOFactor->register($name);

        return $gdOFactor;
    }

    private function buildEmissionFactor(ImportGdOFactorRequest $request, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, Unit::KWh, $source);
    }

    private function buildWellToTankEmissionFactor(ImportGdOFactorRequest $request, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->wellToTankCo2);
        $ch4 = $this->decimal($request->wellToTankCh4);
        $n2o = $this->decimal($request->wellToTankN2o);
        $co2eq = $this->decimal($request->wellToTankCo2eq);
        $source = '' === trim((string) $request->wellToTankSource) ? null : $request->wellToTankSource;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, Unit::KWh, $source);
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
        return $command instanceof ImportGdOFactorRequest;
    }
}
