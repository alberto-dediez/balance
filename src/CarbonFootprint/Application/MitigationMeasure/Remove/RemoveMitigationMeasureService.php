<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasure\Remove;

use App\CarbonFootprint\Application\MitigationMeasure\MitigationMeasureAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMitigationMeasureService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationMeasureRepository $repository,
        private MitigationMeasureAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveMitigationMeasureRequest $request, User $user): RemoveMitigationMeasureResponse
    {
        $mitigationMeasure = $this->repository->find($request->mitigationMeasureId);

        if (null == $mitigationMeasure) {
            throw new \InvalidArgumentException('Mitigation measure not found');
        }

        $this->authorizer->denyUnlessCanDoThat($mitigationMeasure, SecurityAction::Remove, $user);

        $mitigationMeasure->remove();

        $this->repository->add($mitigationMeasure);

        return new RemoveMitigationMeasureResponse(
            mitigationMeasureId: $mitigationMeasure->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMitigationMeasureRequest;
    }
}
