<?php

declare(strict_types=1);

namespace App\Waste\Application\AlertConfiguration\Register;

use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\AlertConfiguration\AlertConfigurationAuthorizer;
use App\Waste\Application\AlertConfiguration\AlertConfigurationItemDto;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfiguration;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationAlreadyExistsForAccountException;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationId;
use App\Waste\Domain\Model\AlertConfiguration\AlertConfigurationRepository;

class RegisterAlertConfigurationService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AlertConfigurationRepository $alertConfigurationRepository,
        private readonly AlertConfigurationAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterAlertConfigurationRequest $request, User $user): RegisterAlertConfigurationResponse
    {
        $id = AlertConfigurationId::generate();

        $this->authorizer->denyUnlessCanDoThat(new AlertConfiguration(id: $id), SecurityAction::Register, $user);

        assert(null !== $request->accountId);

        if (null !== $this->alertConfigurationRepository->findByAccount($request->accountId)) {
            throw new AlertConfigurationAlreadyExistsForAccountException($request->accountId);
        }

        $config = new AlertConfiguration($id);
        $config->register(
            accountId: $request->accountId,
            items: array_map(
                static fn(AlertConfigurationItemDto $dto) => $dto->toItem(),
                $request->items,
            ),
        );

        $this->alertConfigurationRepository->add($config);

        return new RegisterAlertConfigurationResponse(
            alertConfigurationId: $config->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterAlertConfigurationRequest;
    }
}
