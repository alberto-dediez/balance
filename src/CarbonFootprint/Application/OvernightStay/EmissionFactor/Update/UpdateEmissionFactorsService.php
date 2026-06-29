<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\OvernightStay\EmissionFactor\Update;

use App\CarbonFootprint\Application\OvernightStay\OvernightStayAuthorizer;
use App\CarbonFootprint\Domain\Model\OvernightStay\OvernightStayRepository;
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
        private OvernightStayRepository $overnightStayRepository,
        private OvernightStayAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        foreach ($request->rows as $row) {
            $overnightStay = $this->overnightStayRepository->find($row->overnightStayId());

            if (null === $overnightStay) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($overnightStay, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $overnightStay->removeEmissionFactor($request->year);
            } else {
                $overnightStay->setEmissionFactor($row->toEmissionFactor($request->year));
            }

            $this->overnightStayRepository->add($overnightStay);
        }

        return new UpdateEmissionFactorsResponse(year: $request->year);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEmissionFactorsRequest;
    }
}
