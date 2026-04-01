<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\CriticalPoint\Remove;

use App\FoodLoss\Application\CriticalPoint\CriticalPointAuthorizer;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveCriticalPointService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CriticalPointRepository $criticalPointRepository,
        private CriticalPointAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveCriticalPointRequest $request, User $user): RemoveCriticalPointResponse
    {
        $criticalPoint = $this->criticalPointRepository->find($request->criticalPointId);

        if (null == $criticalPoint) {
            throw new \InvalidArgumentException('Critical point not found');
        }

        $this->authorizer->denyUnlessCanDoThat($criticalPoint, SecurityAction::Remove, $user);

        $criticalPoint->remove();

        $this->criticalPointRepository->add($criticalPoint);

        return new RemoveCriticalPointResponse(
            criticalPointId: $criticalPoint->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveCriticalPointRequest;
    }
}
