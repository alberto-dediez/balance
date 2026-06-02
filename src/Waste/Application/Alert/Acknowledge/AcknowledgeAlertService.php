<?php

declare(strict_types=1);

namespace App\Waste\Application\Alert\Acknowledge;

use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Waste\Application\Alert\AlertAuthorizer;
use App\Waste\Domain\Model\Alert\AlertRepository;

class AcknowledgeAlertService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private readonly AlertRepository $alertRepository,
        private readonly AlertAuthorizer $authorizer,
    ) {}

    public function __invoke(AcknowledgeAlertRequest $request, User $user): AcknowledgeAlertResponse
    {
        $alert = $this->alertRepository->find($request->alertId);

        if (null == $alert) {
            throw new \InvalidArgumentException('Alert not found');
        }

        $this->authorizer->denyUnlessCanDoThat($alert, Action::Edit, $user);

        $alert->acknowledge();

        $this->alertRepository->save($alert);

        return new AcknowledgeAlertResponse(
            alertId: $alert->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof AcknowledgeAlertRequest;
    }
}
