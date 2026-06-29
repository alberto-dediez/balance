<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\GdOFactor\EmissionFactor\Update;

use App\CarbonFootprint\Application\GdOFactor\GdOFactorAuthorizer;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Domain\Model\Unit;
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
        private GdOFactorRepository $gdOFactorRepository,
        private GdOFactorAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        foreach ($request->rows as $row) {
            $gdOFactor = $this->gdOFactorRepository->find($row->gdOFactorId());

            if (null === $gdOFactor) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($gdOFactor, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $gdOFactor->removeEmissionFactor($request->year);
            } else {
                $gdOFactor->setEmissionFactor($row->toEmissionFactor($request->year, Unit::KWh));
            }

            $this->gdOFactorRepository->add($gdOFactor);
        }

        return new UpdateEmissionFactorsResponse(year: $request->year);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEmissionFactorsRequest;
    }
}
