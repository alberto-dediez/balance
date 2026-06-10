<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\Remove;

use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\AlertConfiguration\AlertConfigurationAuthorizer;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;

class RemoveAlertConfigurationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AlertConfigurationRepository $repository,
        private readonly AlertConfigurationAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveAlertConfigurationRequest $request, User $user): RemoveAlertConfigurationResponse
    {
        $config = $this->repository->find($request->alertConfigurationId);

        if (null == $config) {
            throw new \InvalidArgumentException('AlertConfiguration not found');
        }

        $this->authorizer->denyUnlessCanDoThat($config, SecurityAction::Remove, $user);

        $this->repository->remove($config);

        return new RemoveAlertConfigurationResponse(
            alertConfigurationId: $request->alertConfigurationId,
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveAlertConfigurationRequest;
    }
}
