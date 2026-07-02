<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Import;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemCategory;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportMerchandisingItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MerchandisingItemRepository $merchandisingItemRepository,
        private MerchandisingItemAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportMerchandisingItemRequest $request, User $user): ImportMerchandisingItemResponse
    {
        $year = $this->resolveYear($request->year);

        $merchandisingItem = $this->resolveMerchandisingItem($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $merchandisingItem->setEmissionFactor(
            $this->buildEmissionFactor($request, $merchandisingItem->unit(), $year, $group),
            $this->buildRecycledEmissionFactor($request, $merchandisingItem->unit(), $year, $group),
        );

        $this->merchandisingItemRepository->add($merchandisingItem);

        return new ImportMerchandisingItemResponse(
            merchandisingItemId: $merchandisingItem->id(),
        );
    }

    private function resolveMerchandisingItem(ImportMerchandisingItemRequest $request, User $user): MerchandisingItem
    {
        if (!empty($request->merchandisingItem)) {
            $merchandisingItem = $this->merchandisingItemRepository->find(new MerchandisingItemId($request->merchandisingItem));

            if (null === $merchandisingItem) {
                throw new \InvalidArgumentException('Merchandising item not found');
            }

            $this->authorizer->denyUnlessCanDoThat($merchandisingItem, SecurityAction::Edit, $user);

            return $merchandisingItem;
        }

        $name = trim((string) $request->name);

        if ('' === $name) {
            throw new \InvalidArgumentException('A merchandising item or a name is required');
        }

        $merchandisingItem = $this->merchandisingItemRepository->findOneByName($name);

        if (null !== $merchandisingItem) {
            $this->authorizer->denyUnlessCanDoThat($merchandisingItem, SecurityAction::Edit, $user);

            return $merchandisingItem;
        }

        return $this->registerMerchandisingItem($name, $request, $user);
    }

    private function registerMerchandisingItem(string $name, ImportMerchandisingItemRequest $request, User $user): MerchandisingItem
    {
        $id = MerchandisingItemId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MerchandisingItem(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $merchandisingItem = new MerchandisingItem($id);
        $merchandisingItem->register(
            $name,
            MerchandisingItemCategory::tryFrom((string) $request->category) ?? MerchandisingItemCategory::Others,
            Unit::tryFrom((string) $request->unit) ?? Unit::Kilogram,
            '' === trim((string) $request->unitWeight) ? null : $request->unitWeight,
            $isDefaultLocale ? null : $locale,
        );

        return $merchandisingItem;
    }

    private function buildEmissionFactor(ImportMerchandisingItemRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
    {
        $co2 = $this->decimal($request->co2);
        $ch4 = $this->decimal($request->ch4);
        $n2o = $this->decimal($request->n2o);
        $co2eq = $this->decimal($request->co2eq);
        $source = '' === trim((string) $request->source) ? null : $request->source;

        return $group->buildEmissionFactor($co2eq, $co2, $ch4, $n2o, $year, $unit, $source);
    }

    private function buildRecycledEmissionFactor(ImportMerchandisingItemRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportMerchandisingItemRequest;
    }
}
