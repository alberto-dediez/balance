<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprint\ApplyMaterialityDecisions;

use App\CarbonFootprint\Application\CarbonFootprint\Materiality\MaterialityOmitCommentGenerator;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\Scope;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ApplyMaterialityDecisionsToPropertyCarbonFootprintService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
        private MaterialityOmitCommentGenerator $commentGenerator,
    ) {}

    public function __invoke(ApplyMaterialityDecisionsToPropertyCarbonFootprintRequest $request, User $user): ApplyMaterialityDecisionsToPropertyCarbonFootprintResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::Edit, $user);

        if ($propertyCarbonFootprint->isCompleted()) {
            return new ApplyMaterialityDecisionsToPropertyCarbonFootprintResponse(excluded: 0, included: 0);
        }

        $before = array_keys($propertyCarbonFootprint->exclusionsFor(Scope::Scope3));

        $propertyCarbonFootprint->applyMaterialityDecisions($request->decisions, $this->commentGenerator->forDecisions($propertyCarbonFootprint, $request->decisions));

        $this->propertyCarbonFootprintRepository->add($propertyCarbonFootprint);

        $after = array_keys($propertyCarbonFootprint->exclusionsFor(Scope::Scope3));

        return new ApplyMaterialityDecisionsToPropertyCarbonFootprintResponse(
            excluded: count(array_diff($after, $before)),
            included: count(array_diff($before, $after)),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ApplyMaterialityDecisionsToPropertyCarbonFootprintRequest;
    }
}
