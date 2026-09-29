<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationAction\RemoveOfCarbonFootprint;

use App\CarbonFootprint\Application\MitigationAction\MitigationActionAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionFilter;
use App\CarbonFootprint\Domain\Model\MitigationAction\MitigationActionRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;

class RemoveMitigationActionsOfCarbonFootprintService
{
    public function __construct(
        private MitigationActionRepository $mitigationActionRepository,
        private MitigationActionAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveMitigationActionsOfCarbonFootprintRequest $request, User $user): void
    {
        $mitigationActions = $this->mitigationActionRepository->findByFilter(new MitigationActionFilter(
            subjectType: $request->subjectType,
            carbonFootprintId: $request->carbonFootprintId,
            limit: 0,
        ));

        foreach ($mitigationActions as $mitigationAction) {
            $this->authorizer->denyUnlessCanDoThat($mitigationAction, SecurityAction::Remove, $user);

            $mitigationAction->remove();

            $this->mitigationActionRepository->add($mitigationAction);
        }
    }
}
