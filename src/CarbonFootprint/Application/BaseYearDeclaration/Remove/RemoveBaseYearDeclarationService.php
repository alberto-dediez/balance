<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\BaseYearDeclaration\Remove;

use App\CarbonFootprint\Application\BaseYearDeclaration\BaseYearDeclarationAuthorizer;
use App\CarbonFootprint\Domain\Model\BaseYearDeclaration\BaseYearDeclarationRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveBaseYearDeclarationService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private BaseYearDeclarationRepository $baseYearDeclarationRepository,
        private BaseYearDeclarationAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveBaseYearDeclarationRequest $request, User $user): RemoveBaseYearDeclarationResponse
    {
        $declaration = $this->baseYearDeclarationRepository->find($request->baseYearDeclarationId);

        if (null === $declaration) {
            throw new \InvalidArgumentException('BaseYearDeclaration not found');
        }

        $this->authorizer->denyUnlessCanDoThat($declaration, Action::Remove, $user);

        $declaration->remove();

        $this->baseYearDeclarationRepository->remove($declaration);

        return new RemoveBaseYearDeclarationResponse($declaration);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof RemoveBaseYearDeclarationRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveBaseYearDeclarationRequest;
    }
}
