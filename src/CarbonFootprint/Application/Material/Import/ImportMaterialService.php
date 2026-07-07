<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\Import;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Material\Material;
use App\CarbonFootprint\Domain\Model\Material\MaterialCategory;
use App\CarbonFootprint\Domain\Model\Material\MaterialId;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportMaterialService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialRepository $materialRepository,
        private MaterialAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportMaterialRequest $request, User $user): ImportMaterialResponse
    {
        $year = $this->resolveYear($request->year);

        $material = $this->resolveMaterial($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $material->setEmissionFactor(
            $this->buildEmissionFactor($request, $material->unit(), $year, $group),
            $this->buildRecycledEmissionFactor($request, $material->unit(), $year, $group),
        );

        $this->materialRepository->add($material);

        return new ImportMaterialResponse(
            materialId: $material->id(),
        );
    }

    private function resolveMaterial(ImportMaterialRequest $request, User $user): Material
    {
        if (!empty($request->material)) {
            $material = $this->materialRepository->find(new MaterialId($request->material));

            if (null === $material) {
                throw new \InvalidArgumentException('Material not found');
            }

            $this->authorizer->denyUnlessCanDoThat($material, SecurityAction::Edit, $user);

            return $material;
        }

        $name = trim((string) $request->name);

        if ('' === $name) {
            throw new \InvalidArgumentException('A material or a name is required');
        }

        return $this->registerMaterial($name, $request, $user);
    }

    private function registerMaterial(string $name, ImportMaterialRequest $request, User $user): Material
    {
        $id = MaterialId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Material(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $material = new Material($id);
        $material->register(
            $name,
            MaterialCategory::tryFrom((string) $request->category) ?? MaterialCategory::Others,
            Unit::tryFrom((string) $request->unit) ?? Unit::Kilogram,
            $isDefaultLocale ? null : $locale,
        );

        return $material;
    }

    private function buildEmissionFactor(ImportMaterialRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, $unit, $source);
    }

    private function buildRecycledEmissionFactor(ImportMaterialRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->recycledCo2);
        $ch4 = $this->decimal($request->recycledCh4);
        $n2o = $this->decimal($request->recycledN2o);
        $co2eq = $this->decimal($request->recycledCo2eq);
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
        return $command instanceof ImportMaterialRequest;
    }
}
