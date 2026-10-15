<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Remove;

use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveProcessService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private ProcessRepository $repository, private ProcessAuthorizer $authorizer) {}

    public function __invoke(RemoveProcessRequest $request, User $user): RemoveProcessResponse
    {
        $process = $this->repository->find($request->processId);

        if (null == $process) {
            throw new \InvalidArgumentException('Process not found');
        }

        $this->authorizer->denyUnlessCanDoThat($process, SecurityAction::Remove, $user);

        $process->remove();

        $this->repository->add($process);

        return new RemoveProcessResponse(
            processId: $process->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveProcessRequest;
    }
}
