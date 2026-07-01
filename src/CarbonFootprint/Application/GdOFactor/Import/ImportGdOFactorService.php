<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\Import;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
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
    ) {}

    public function __invoke(ImportGdOFactorRequest $request, User $user): ImportGdOFactorResponse
    {
        $year = $this->resolveYear($request->year);

        $gdOFactor = $this->resolveGdOFactor($request, $user);

        $gdOFactor->setEmissionFactor($this->buildEmissionFactor($request, $year));

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

        $gdOFactor = $this->gdOFactorRepository->findOneByName($name);

        if (null !== $gdOFactor) {
            $this->authorizer->denyUnlessCanDoThat($gdOFactor, SecurityAction::Edit, $user);

            return $gdOFactor;
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

    private function buildEmissionFactor(ImportGdOFactorRequest $request, int $year): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);

        if (0.0 === (float) $co2eq) {
            // Temporary FASE 2 compatibility fallback: real EmissionFactorGroup resolution lands in FASE 3/7/8/9.
            $co2eq = Gwp::ar6()->co2eqFor($co2, $ch4, $n2o);
        }

        return new EmissionFactor(
            co2eq: $co2eq,
            co2: $co2,
            ch4: $ch4,
            n2o: $n2o,
            year: $year,
            emissionFactorGroupId: EmissionFactorGroupId::default(),
            unit: Unit::KWh,
            source: '' === trim((string) $request->source) ? null : $request->source,
        );
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
