<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\SurplusCause\Remove;

use App\FoodLoss\Application\SurplusCause\SurplusCauseAuthorizer;
use App\FoodLoss\Domain\Model\SurplusCause\SurplusCauseRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveSurplusCauseService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private SurplusCauseRepository $repository, private SurplusCauseAuthorizer $authorizer) {}

    public function __invoke(RemoveSurplusCauseRequest $request, User $user): RemoveSurplusCauseResponse
    {
        $surplusCause = $this->repository->find($request->surplusCauseId);

        if (null == $surplusCause) {
            throw new \InvalidArgumentException('Surplus cause not found');
        }

        $this->authorizer->denyUnlessCanDoThat($surplusCause, Action::Remove, $user);

        $surplusCause->remove();

        $this->repository->add($surplusCause);

        return new RemoveSurplusCauseResponse(
            surplusCauseId: $surplusCause->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveSurplusCauseRequest;
    }
}
