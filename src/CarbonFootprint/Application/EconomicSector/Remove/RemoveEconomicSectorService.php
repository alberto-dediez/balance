<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\EconomicSector\Remove;

use App\CarbonFootprint\Application\EconomicSector\EconomicSectorAuthorizer;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveEconomicSectorService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private EconomicSectorRepository $repository, private EconomicSectorAuthorizer $authorizer) {}

    public function __invoke(RemoveEconomicSectorRequest $request, User $user): RemoveEconomicSectorResponse
    {
        $economicSector = $this->repository->find($request->economicSectorId);

        if (null == $economicSector) {
            throw new \InvalidArgumentException('EconomicSector not found');
        }

        $this->authorizer->denyUnlessCanDoThat($economicSector, SecurityAction::Remove, $user);

        $economicSector->remove();

        $this->repository->add($economicSector);

        return new RemoveEconomicSectorResponse(
            economicSectorId: $economicSector->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveEconomicSectorRequest;
    }
}
