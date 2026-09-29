<?php

declare(strict_types=1);

namespace App\Materiality\Application\MaterialitySector\Remove;

use App\Materiality\Application\MaterialitySector\MaterialitySectorAuthorizer;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorNotFoundException;
use App\Materiality\Domain\Model\MaterialitySector\MaterialitySectorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMaterialitySectorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MaterialitySectorRepository $materialitySectorRepository,
        private MaterialitySectorAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveMaterialitySectorRequest $request, User $user): RemoveMaterialitySectorResponse
    {
        $materialitySector = $this->materialitySectorRepository->find($request->materialitySectorId);

        if (null == $materialitySector) {
            throw new MaterialitySectorNotFoundException($request->materialitySectorId);
        }

        $this->authorizer->denyUnlessCanDoThat($materialitySector, SecurityAction::Remove, $user);

        $materialitySector->remove();

        $this->materialitySectorRepository->add($materialitySector);

        return new RemoveMaterialitySectorResponse(
            materialitySectorId: $materialitySector->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMaterialitySectorRequest;
    }
}
