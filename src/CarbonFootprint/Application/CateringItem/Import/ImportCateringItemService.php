<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Import;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemCategory;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\Gwp;
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
    ) {}

    public function __invoke(ImportCateringItemRequest $request, User $user): ImportCateringItemResponse
    {
        $year = $this->resolveYear($request->year);

        $cateringItem = $this->resolveCateringItem($request, $user);

        $cateringItem->setEmissionFactor(
            $this->buildEmissionFactor($request, $cateringItem->unit(), $year),
            $this->buildRecycledEmissionFactor($request, $cateringItem->unit(), $year),
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

        $cateringItem = $this->cateringItemRepository->findOneByName($name);

        if (null !== $cateringItem) {
            $this->authorizer->denyUnlessCanDoThat($cateringItem, SecurityAction::Edit, $user);

            return $cateringItem;
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

    private function buildEmissionFactor(ImportCateringItemRequest $request, Unit $unit, int $year): EmissionFactor
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
            unit: $unit,
            source: '' === trim((string) $request->source) ? null : $request->source,
        );
    }

    private function buildRecycledEmissionFactor(ImportCateringItemRequest $request, Unit $unit, int $year): EmissionFactor
    {
        $co2 = $this->decimal($request->recycledCo2);
        $ch4 = $this->decimal($request->recycledCh4);
        $n2o = $this->decimal($request->recycledN2o);
        $co2eq = $this->decimal($request->recycledCo2eq);

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
            unit: $unit,
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
        return $command instanceof ImportCateringItemRequest;
    }
}
