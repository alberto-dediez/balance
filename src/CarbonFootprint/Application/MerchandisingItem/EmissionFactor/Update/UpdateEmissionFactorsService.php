<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\Update;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
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
        private MerchandisingItemRepository $merchandisingItemRepository,
        private MerchandisingItemAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        foreach ($request->rows as $row) {
            $merchandisingItem = $this->merchandisingItemRepository->find($row->merchandisingItemId());

            if (null === $merchandisingItem) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($merchandisingItem, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $merchandisingItem->removeEmissionFactor($request->year, $row->emissionFactorGroupId());
            } else {
                $emissionFactorGroup = $this->resolveEmissionFactorGroup($row->emissionFactorGroupId);

                $merchandisingItem->setEmissionFactor(
                    $row->newEmissionFactor($request->year, $merchandisingItem->unit(), $emissionFactorGroup),
                    $row->recycledEmissionFactor($request->year, $merchandisingItem->unit(), $emissionFactorGroup),
                );
            }

            $this->merchandisingItemRepository->add($merchandisingItem);
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
