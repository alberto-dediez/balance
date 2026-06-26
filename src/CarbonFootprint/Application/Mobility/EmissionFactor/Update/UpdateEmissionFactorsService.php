<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Mobility\EmissionFactor\Update;

use App\CarbonFootprint\Application\Mobility\MobilityAuthorizer;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityRepository;
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
        private MobilityRepository $mobilityRepository,
        private MobilityAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        foreach ($request->rows as $row) {
            $mobility = $this->mobilityRepository->find($row->mobilityId());

            if (null === $mobility) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($mobility, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $mobility->removeEmissionFactor($request->year);
            } else {
                $mobility->setEmissionFactor($row->toEmissionFactor($request->year, $mobility->unit()));
            }

            $this->mobilityRepository->add($mobility);
        }

        return new UpdateEmissionFactorsResponse(year: $request->year);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEmissionFactorsRequest;
    }
}
