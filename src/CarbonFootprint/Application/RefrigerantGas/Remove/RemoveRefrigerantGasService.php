<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Remove;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveRefrigerantGasService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private RefrigerantGasRepository $repository, private RefrigerantGasAuthorizer $authorizer) {}

    public function __invoke(RemoveRefrigerantGasRequest $request, User $user): RemoveRefrigerantGasResponse
    {
        $refrigerantGas = $this->repository->find($request->refrigerantGasId);

        if (null == $refrigerantGas) {
            throw new \InvalidArgumentException('RefrigerantGas not found');
        }

        $this->authorizer->denyUnlessCanDoThat($refrigerantGas, SecurityAction::Remove, $user);

        $refrigerantGas->remove();

        $this->repository->add($refrigerantGas);

        return new RemoveRefrigerantGasResponse(
            refrigerantGasId: $refrigerantGas->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveRefrigerantGasRequest;
    }
}
