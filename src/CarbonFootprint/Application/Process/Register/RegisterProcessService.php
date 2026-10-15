<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Process\Register;

use App\CarbonFootprint\Application\Process\ProcessAuthorizer;
use App\CarbonFootprint\Domain\Model\Process\Process;
use App\CarbonFootprint\Domain\Model\Process\ProcessId;
use App\CarbonFootprint\Domain\Model\Process\ProcessRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterProcessService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ProcessRepository $processRepository,
        private ProcessAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterProcessRequest $request, User $user): RegisterProcessResponse
    {
        $id = ProcessId::generate();

        $this->authorizer->denyUnlessCanDoThat(new Process(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $process = new Process($id);
        $process->register(
            $request->name,
            $request->unit,
            $isDefaultLocale ? null : $locale,
        );

        $this->processRepository->add($process);

        return new RegisterProcessResponse(
            processId: $process->id(),
            name: $process->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterProcessRequest;
    }
}
