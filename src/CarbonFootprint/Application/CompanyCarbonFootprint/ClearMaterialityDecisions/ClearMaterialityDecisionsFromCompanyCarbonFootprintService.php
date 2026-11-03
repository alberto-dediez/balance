<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\ClearMaterialityDecisions;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ClearMaterialityDecisionsFromCompanyCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $authorizer,
    ) {}

    public function __invoke(ClearMaterialityDecisionsFromCompanyCarbonFootprintRequest $request, User $user): ClearMaterialityDecisionsFromCompanyCarbonFootprintResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint || $companyCarbonFootprint->isRemoved()) {
            return new ClearMaterialityDecisionsFromCompanyCarbonFootprintResponse(cleared: false);
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Edit, $user);

        if ($companyCarbonFootprint->isCompleted() || [] === $companyCarbonFootprint->materialityDecisions()) {
            return new ClearMaterialityDecisionsFromCompanyCarbonFootprintResponse(cleared: false);
        }

        $companyCarbonFootprint->clearMaterialityDecisions();

        $this->companyCarbonFootprintRepository->add($companyCarbonFootprint);

        return new ClearMaterialityDecisionsFromCompanyCarbonFootprintResponse(cleared: true);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ClearMaterialityDecisionsFromCompanyCarbonFootprintRequest;
    }
}
