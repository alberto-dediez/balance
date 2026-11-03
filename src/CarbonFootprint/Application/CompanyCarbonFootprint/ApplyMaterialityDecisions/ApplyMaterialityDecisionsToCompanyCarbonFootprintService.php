<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CompanyCarbonFootprint\ApplyMaterialityDecisions;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialityOmitCommentGenerator;
use App\CarbonFootprint\Application\CompanyCarbonFootprint\CompanyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\CompanyCarbonFootprint\CompanyCarbonFootprintRepository;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ApplyMaterialityDecisionsToCompanyCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CompanyCarbonFootprintRepository $companyCarbonFootprintRepository,
        private CompanyCarbonFootprintAuthorizer $authorizer,
        private MaterialityOmitCommentGenerator $commentGenerator,
    ) {}

    public function __invoke(ApplyMaterialityDecisionsToCompanyCarbonFootprintRequest $request, User $user): ApplyMaterialityDecisionsToCompanyCarbonFootprintResponse
    {
        $companyCarbonFootprint = $this->companyCarbonFootprintRepository->find($request->companyCarbonFootprintId);

        if (null === $companyCarbonFootprint) {
            throw new \InvalidArgumentException('CompanyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($companyCarbonFootprint, SecurityAction::Edit, $user);

        if ($companyCarbonFootprint->isCompleted()) {
            return new ApplyMaterialityDecisionsToCompanyCarbonFootprintResponse(excluded: 0, included: 0);
        }

        $before = array_keys($companyCarbonFootprint->exclusionsFor(Scope::Scope3));

        $companyCarbonFootprint->applyMaterialityDecisions($request->decisions, $this->commentGenerator->forDecisions($companyCarbonFootprint, $request->decisions));

        $this->companyCarbonFootprintRepository->add($companyCarbonFootprint);

        $after = array_keys($companyCarbonFootprint->exclusionsFor(Scope::Scope3));

        return new ApplyMaterialityDecisionsToCompanyCarbonFootprintResponse(
            excluded: count(array_diff($after, $before)),
            included: count(array_diff($before, $after)),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ApplyMaterialityDecisionsToCompanyCarbonFootprintRequest;
    }
}
