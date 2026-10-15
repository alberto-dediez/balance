<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Import;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodCategory;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportCapitalGoodService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CapitalGoodRepository $capitalGoodRepository,
        private CapitalGoodAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportCapitalGoodRequest $request, User $user): ImportCapitalGoodResponse
    {
        $year = $this->resolveYear($request->year);

        $capitalGood = $this->resolveCapitalGood($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $capitalGood->setEmissionFactor($this->buildEmissionFactor($request, $capitalGood->unit(), $year, $group));

        $this->capitalGoodRepository->add($capitalGood);

        return new ImportCapitalGoodResponse(
            capitalGoodId: $capitalGood->id(),
        );
    }

    private function resolveCapitalGood(ImportCapitalGoodRequest $request, User $user): CapitalGood
    {
        if (!empty($request->capitalGood)) {
            $capitalGood = $this->capitalGoodRepository->find(new CapitalGoodId($request->capitalGood));

            if (null === $capitalGood) {
                throw new \InvalidArgumentException('Capital good not found');
            }

            $this->authorizer->denyUnlessCanDoThat($capitalGood, SecurityAction::Edit, $user);

            return $capitalGood;
        }

        $name = trim((string) $request->name);

        if ('' === $name) {
            throw new \InvalidArgumentException('A capital good or a name is required');
        }

        return $this->registerCapitalGood($name, $request, $user);
    }

    private function registerCapitalGood(string $name, ImportCapitalGoodRequest $request, User $user): CapitalGood
    {
        $id = CapitalGoodId::generate();

        $this->authorizer->denyUnlessCanDoThat(new CapitalGood(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $capitalGood = new CapitalGood($id);
        $capitalGood->register(
            $name,
            CapitalGoodCategory::tryFrom((string) $request->category) ?? CapitalGoodCategory::Others,
            Unit::tryFrom((string) $request->unit) ?? Unit::Euro,
            $isDefaultLocale ? null : $locale,
        );

        return $capitalGood;
    }

    private function buildEmissionFactor(ImportCapitalGoodRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportCapitalGoodRequest;
    }
}
