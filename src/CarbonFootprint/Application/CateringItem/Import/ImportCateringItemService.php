<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Import;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
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

class ImportCateringItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CateringItemRepository $cateringItemRepository,
        private CateringItemAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportCateringItemRequest $request, User $user): ImportCateringItemResponse
    {
        $year = $this->resolveYear($request->year);

        $cateringItem = $this->resolveCateringItem($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $cateringItem->setEmissionFactor(
            $this->buildEmissionFactor($request, $cateringItem->unit(), $year, $group),
            $this->buildRecycledEmissionFactor($request, $cateringItem->unit(), $year, $group),
        );

        $this->cateringItemRepository->add($cateringItem);

        return new ImportCateringItemResponse(
            cateringItemId: $cateringItem->id(),
        );
    }

    private function resolveCateringItem(ImportCateringItemRequest $request, User $user): CateringItem
    {
        if (!empty($request->cateringItem)) {
            $cateringItem = $this->cateringItemRepository->find(new CateringItemId($request->cateringItem));

            if (null === $cateringItem) {
                throw new \InvalidArgumentException('Catering item not found');
            }

            $this->authorizer->denyUnlessCanDoThat($cateringItem, SecurityAction::Edit, $user);

            return $cateringItem;
        }

        $name = trim((string) $request->name);

        if ('' === $name) {
            throw new \InvalidArgumentException('A catering item or a name is required');
        }

        return $this->registerCateringItem($name, $request, $user);
    }

    private function registerCateringItem(string $name, ImportCateringItemRequest $request, User $user): CateringItem
    {
        $id = CateringItemId::generate();

        $this->authorizer->denyUnlessCanDoThat(new CateringItem(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $cateringItem = new CateringItem($id);
        $cateringItem->register(
            $name,
            CateringItemCategory::tryFrom((string) $request->category) ?? CateringItemCategory::Others,
            Unit::tryFrom((string) $request->unit) ?? Unit::Kilogram,
            '' === trim((string) $request->unitWeight) ? null : $request->unitWeight,
            $isDefaultLocale ? null : $locale,
        );

        return $cateringItem;
    }

    private function buildEmissionFactor(ImportCateringItemRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, $unit, $source);
    }

    private function buildRecycledEmissionFactor(ImportCateringItemRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportCateringItemRequest;
    }
}
