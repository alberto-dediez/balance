<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\Register;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Application\Protocol\ProtocolUploader;
use App\FoodLoss\Domain\Model\Protocol\Protocol;
use App\FoodLoss\Domain\Model\Protocol\ProtocolId;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterProtocolService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProtocolRepository $protocolRepository,
        private ProtocolAuthorizer $authorizer,
        private ProtocolUploader $fileUploader,
    ) {}

    public function __invoke(RegisterProtocolRequest $request, User $user): RegisterProtocolResponse
    {
        $id = ProtocolId::generate();

        assert($request->accountId instanceof AccountId);

        $this->authorizer->denyUnlessCanDoThat(
            new Protocol(id: $id, accountId: $request->accountId),
            SecurityAction::Register,
            $user,
        );

        $path = $this->fileUploader->upload($request->temporaryFilePath, $request->fileExtension, $request->accountId);

        $protocol = new Protocol($id);
        $protocol->register(
            name: $request->name,
            description: $request->description,
            path: $path,
            accountId: $request->accountId,
        );

        $this->protocolRepository->add($protocol);

        return new RegisterProtocolResponse(
            protocolId: $protocol->id(),
            name: $protocol->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterProtocolRequest;
    }
}
