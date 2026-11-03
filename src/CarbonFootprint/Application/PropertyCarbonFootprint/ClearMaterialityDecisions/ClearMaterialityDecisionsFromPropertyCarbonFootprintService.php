<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\ClearMaterialityDecisions;

use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ClearMaterialityDecisionsFromPropertyCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(ClearMaterialityDecisionsFromPropertyCarbonFootprintRequest $request, User $user): ClearMaterialityDecisionsFromPropertyCarbonFootprintResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint || $propertyCarbonFootprint->isRemoved()) {
            return new ClearMaterialityDecisionsFromPropertyCarbonFootprintResponse(cleared: false);
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::Edit, $user);

        if ($propertyCarbonFootprint->isCompleted() || [] === $propertyCarbonFootprint->materialityDecisions()) {
            return new ClearMaterialityDecisionsFromPropertyCarbonFootprintResponse(cleared: false);
        }

        $propertyCarbonFootprint->clearMaterialityDecisions();

        $this->propertyCarbonFootprintRepository->add($propertyCarbonFootprint);

        return new ClearMaterialityDecisionsFromPropertyCarbonFootprintResponse(cleared: true);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ClearMaterialityDecisionsFromPropertyCarbonFootprintRequest;
    }
}
