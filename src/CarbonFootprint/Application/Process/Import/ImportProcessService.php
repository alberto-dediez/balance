<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Import;

use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportProcessService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProcessRepository $processRepository,
        private ProcessAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportProcessRequest $request, User $user): ImportProcessResponse
    {
        $year = $this->resolveYear($request->year);

        $process = $this->resolveProcess($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $process->setEmissionFactor($this->buildEmissionFactor($request, $process->unit(), $year, $group));

        $this->processRepository->add($process);

        return new ImportProcessResponse(
            processId: $process->id(),
        );
    }

    private function resolveProcess(ImportProcessRequest $request, User $user): Process
    {
        if (!empty($request->process)) {
            $process = $this->processRepository->find(new ProcessId($request->process));

            if (null === $process) {
                throw new \InvalidArgumentException('Process not found');
            }

            $this->authorizer->denyUnlessCanDoThat($process, SecurityAction::Edit, $user);

            return $process;
        }

        $name = trim((string) $request->name);

        if ('' === $name) {
            throw new \InvalidArgumentException('A process or a name is required');
        }

        return $this->registerProcess($name, $request, $user);
    }

    private function registerProcess(string $name, ImportProcessRequest $request, User $user): Process
    {
        $id = ProcessId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Process(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $process = new Process($id);
        $process->register(
            $name,
            Unit::tryFrom((string) $request->unit) ?? Unit::Piece,
            $isDefaultLocale ? null : $locale,
        );

        return $process;
    }

    private function buildEmissionFactor(ImportProcessRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportProcessRequest;
    }
}
