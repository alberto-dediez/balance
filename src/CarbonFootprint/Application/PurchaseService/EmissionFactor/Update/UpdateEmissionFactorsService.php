<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\EmissionFactor\Update;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateEmissionFactorsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PurchaseServiceRepository $purchaseServiceRepository,
        private PurchaseServiceAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new PurchaseService(id: PurchaseServiceId::generate()), SecurityAction::Edit, $user);

        foreach ($request->rows as $row) {
            $purchaseService = $this->purchaseServiceRepository->find($row->purchaseServiceId());

            if (null === $purchaseService) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($purchaseService, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $purchaseService->removeEmissionFactor($request->year, $row->emissionFactorGroupId());
            } else {
                $emissionFactorGroup = $this->resolveEmissionFactorGroup($row->emissionFactorGroupId);

                $purchaseService->setEmissionFactor(
                    $row->toEmissionFactor($request->year, $purchaseService->unit(), $emissionFactorGroup),
                );
            }

            $this->purchaseServiceRepository->add($purchaseService);
        }

        return new UpdateEmissionFactorsResponse(year: $request->year);
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
        return $command instanceof UpdateEmissionFactorsRequest;
    }
}
