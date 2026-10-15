<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Update;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactor\EmissionFactor;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdatePurchaseServiceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PurchaseServiceRepository $purchaseServiceRepository,
        private PurchaseServiceAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdatePurchaseServiceRequest $request, User $user): UpdatePurchaseServiceResponse
    {
        $purchaseService = $this->purchaseServiceRepository->find($request->purchaseServiceId);

        if (null == $purchaseService) {
            throw new \InvalidArgumentException('PurchaseService not found');
        }

        $this->authorizer->denyUnlessCanDoThat($purchaseService, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $purchaseService->update(
            name: $request->name,
            category: $request->category,
            unit: $request->unit,
            locale: $isDefaultLocale ? null : $locale,
        );

        $unit = $purchaseService->unit();

        $purchaseService->syncEmissionFactors(
            array_values(array_map(
                fn(EmissionFactorData $data): EmissionFactor => $data->toEmissionFactor(
                    $unit,
                    $this->resolveEmissionFactorGroup($data->emissionFactorGroupId),
                ),
                $request->emissionFactors,
            )),
        );

        $this->purchaseServiceRepository->add($purchaseService);

        return new UpdatePurchaseServiceResponse(
            purchaseServiceId: $purchaseService->id(),
            name: $purchaseService->name(),
        );
    }

    private function resolveEmissionFactorGroup(?string $emissionFactorGroupId): EmissionFactorGroup
    {
        $emissionFactorGroup = null !== $emissionFactorGroupId
            ? $this->emissionFactorGroupRepository->find(new EmissionFactorGroupId($emissionFactorGroupId))
            : null;

        if (null === $emissionFactorGroup) {
            throw new \InvalidArgumentException('Emission factor group not found');
        }

        return $emissionFactorGroup;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdatePurchaseServiceRequest;
    }
}
