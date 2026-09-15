<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\RefrigerantGas\Register;

use App\CarbonFootprint\Application\RefrigerantGas\RefrigerantGasAuthorizer;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGas;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasId;
use App\CarbonFootprint\Domain\Model\RefrigerantGas\RefrigerantGasRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterRefrigerantGasService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private RefrigerantGasRepository $refrigerantGasRepository,
        private RefrigerantGasAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterRefrigerantGasRequest $request, User $user): RegisterRefrigerantGasResponse
    {
        $id = RefrigerantGasId::generate();

        $this->authorizer->denyUnlessCanDoThat(new RefrigerantGas(id: $id), SecurityAction::List, $user);

        $refrigerantGas = new RefrigerantGas($id);
        $refrigerantGas->register($request->name);

        $this->refrigerantGasRepository->add($refrigerantGas);

        return new RegisterRefrigerantGasResponse(
            refrigerantGasId: $refrigerantGas->id(),
            name: $refrigerantGas->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterRefrigerantGasRequest;
    }
}
