<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Waste\EmissionFactor\Update;

use App\CarbonFootprint\Application\Waste\WasteAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Domain\Model\Waste\WasteRepository;
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
        private WasteRepository $wasteRepository,
        private WasteAuthorizer $authorizer,
        private EmissionFactorGroupRepository $emissionFactorGroupRepository,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        foreach ($request->rows as $row) {
            $waste = $this->wasteRepository->find($row->wasteId());

            if (null === $waste) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($waste, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $waste->removeEmissionFactor($request->year, $row->emissionFactorGroupId());
            } else {
                $emissionFactorGroup = $this->resolveEmissionFactorGroup($row->emissionFactorGroupId);

                $waste->setEmissionFactor($row->toEmissionFactor($request->year, $waste->unit(), $emissionFactorGroup));
            }

            $this->wasteRepository->add($waste);
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
