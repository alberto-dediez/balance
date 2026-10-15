<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Remove;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveCapitalGoodService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private CapitalGoodRepository $repository, private CapitalGoodAuthorizer $authorizer) {}

    public function __invoke(RemoveCapitalGoodRequest $request, User $user): RemoveCapitalGoodResponse
    {
        $capitalGood = $this->repository->find($request->capitalGoodId);

        if (null == $capitalGood) {
            throw new \InvalidArgumentException('CapitalGood not found');
        }

        $this->authorizer->denyUnlessCanDoThat($capitalGood, SecurityAction::Remove, $user);

        $capitalGood->remove();

        $this->repository->add($capitalGood);

        return new RemoveCapitalGoodResponse(
            capitalGoodId: $capitalGood->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveCapitalGoodRequest;
    }
}
