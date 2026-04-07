<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\Protocol\Update;

use App\FoodLoss\Application\Protocol\ProtocolAuthorizer;
use App\FoodLoss\Application\Protocol\ProtocolUploader;
use App\FoodLoss\Domain\Model\Protocol\ProtocolRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateProtocolService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProtocolRepository $protocolRepository,
        private ProtocolAuthorizer $authorizer,
        private ProtocolUploader $fileUploader,
    ) {}

    public function __invoke(UpdateProtocolRequest $request, User $user): UpdateProtocolResponse
    {
        $protocol = $this->protocolRepository->find($request->protocolId);

        if (null == $protocol) {
            throw new \InvalidArgumentException('Protocol not found');
        }

        $this->authorizer->denyUnlessCanDoThat($protocol, SecurityAction::Edit, $user);

        if (null !== $request->temporaryFilePath && null !== $request->fileExtension) {
            assert($protocol->accountId() instanceof AccountId);
            $path = $this->fileUploader->upload($request->temporaryFilePath, $request->fileExtension, $protocol->accountId());
        } else {
            $path = $protocol->path();
        }

        $protocol->update(
            name: $request->name,
            description: $request->description,
            path: $path,
        );

        $this->protocolRepository->add($protocol);

        return new UpdateProtocolResponse(
            protocolId: $protocol->id(),
            name: $protocol->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateProtocolRequest;
    }
}
