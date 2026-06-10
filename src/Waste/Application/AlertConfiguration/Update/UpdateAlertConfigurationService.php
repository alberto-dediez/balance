<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\Update;

use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\AlertConfiguration\AlertConfigurationAuthorizer;
use App\Waste\Application\AlertConfiguration\AlertConfigurationItemDto;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;

class UpdateAlertConfigurationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AlertConfigurationRepository $alertConfigurationRepository,
        private readonly AlertConfigurationAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateAlertConfigurationRequest $request, User $user): UpdateAlertConfigurationResponse
    {
        $config = $this->alertConfigurationRepository->find($request->alertConfigurationId);

        if (null == $config) {
            throw new \InvalidArgumentException('AlertConfiguration not found');
        }

        $this->authorizer->denyUnlessCanDoThat($config, SecurityAction::Edit, $user);

        $config->update(items: array_map(
            static fn(AlertConfigurationItemDto $dto) => $dto->toItem(),
            $request->items,
        ));

        $this->alertConfigurationRepository->save($config);

        return new UpdateAlertConfigurationResponse(
            alertConfigurationId: $config->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateAlertConfigurationRequest;
    }
}
