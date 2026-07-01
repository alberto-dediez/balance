<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Combustible\EmissionFactor\Update;

use App\CarbonFootprint\Application\Combustible\CombustibleAuthorizer;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateEmissionFactorsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CombustibleRepository $combustibleRepository,
        private CombustibleAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        foreach ($request->rows as $row) {
            $combustible = $this->combustibleRepository->find($row->combustibleId());

            if (null === $combustible) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($combustible, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $combustible->removeEmissionFactor($request->year, EmissionFactorGroupId::default());
            } else {
                $combustible->setEmissionFactor($row->toEmissionFactor($request->year, $combustible->unit()));
            }

            $this->combustibleRepository->add($combustible);
        }

        return new UpdateEmissionFactorsResponse(year: $request->year);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEmissionFactorsRequest;
    }
}
