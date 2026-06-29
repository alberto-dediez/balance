<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\Remove;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveOvernightStayService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private OvernightStayRepository $repository, private OvernightStayAuthorizer $authorizer) {}

    public function __invoke(RemoveOvernightStayRequest $request, User $user): RemoveOvernightStayResponse
    {
        $overnightStay = $this->repository->find($request->overnightStayId);

        if (null == $overnightStay) {
            throw new \InvalidArgumentException('OvernightStay not found');
        }

        $this->authorizer->denyUnlessCanDoThat($overnightStay, SecurityAction::Remove, $user);

        $overnightStay->remove();

        $this->repository->add($overnightStay);

        return new RemoveOvernightStayResponse(
            overnightStayId: $overnightStay->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveOvernightStayRequest;
    }
}
