<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Import;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceCategory;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\CarbonFootprint\Domain\Model\Unit;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class ImportPurchaseServiceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PurchaseServiceRepository $purchaseServiceRepository,
        private PurchaseServiceAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(ImportPurchaseServiceRequest $request, User $user): ImportPurchaseServiceResponse
    {
        $year = $this->resolveYear($request->year);

        $purchaseService = $this->resolvePurchaseService($request, $user);

        $group = $this->resolveEmissionFactorGroup($request->emissionFactorGroup);

        $purchaseService->setEmissionFactor($this->buildEmissionFactor($request, $purchaseService->unit(), $year, $group));

        $this->purchaseServiceRepository->add($purchaseService);

        return new ImportPurchaseServiceResponse(
            purchaseServiceId: $purchaseService->id(),
        );
    }

    private function resolvePurchaseService(ImportPurchaseServiceRequest $request, User $user): PurchaseService
    {
        if (!empty($request->purchaseService)) {
            $purchaseService = $this->purchaseServiceRepository->find(new PurchaseServiceId($request->purchaseService));

            if (null === $purchaseService) {
                throw new \InvalidArgumentException('Purchase service not found');
            }

            $this->authorizer->denyUnlessCanDoThat($purchaseService, SecurityAction::Edit, $user);

            return $purchaseService;
        }

        $name = trim((string) $request->name);

        if ('' === $name) {
            throw new \InvalidArgumentException('A purchase service or a name is required');
        }

        return $this->registerPurchaseService($name, $request, $user);
    }

    private function registerPurchaseService(string $name, ImportPurchaseServiceRequest $request, User $user): PurchaseService
    {
        $id = PurchaseServiceId::generate();

        $this->authorizer->denyUnlessCanDoThat(new PurchaseService(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $purchaseService = new PurchaseService($id);
        $purchaseService->register(
            $name,
            PurchaseServiceCategory::tryFrom((string) $request->category) ?? PurchaseServiceCategory::Others,
            Unit::tryFrom((string) $request->unit) ?? Unit::Euro,
            $isDefaultLocale ? null : $locale,
        );

        return $purchaseService;
    }

    private function buildEmissionFactor(ImportPurchaseServiceRequest $request, Unit $unit, int $year, EmissionFactorGroup $group): EmissionFactor
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
        return $command instanceof ImportPurchaseServiceRequest;
    }
}
