<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\Material\EmissionFactor\Update;

use App\CarbonFootprint\Application\Material\MaterialAuthorizer;
use App\CarbonFootprint\Domain\Model\Material\MaterialRepository;
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
        private MaterialRepository $materialRepository,
        private MaterialAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        foreach ($request->rows as $row) {
            $material = $this->materialRepository->find($row->materialId());

            if (null === $material) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($material, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $material->removeEmissionFactor($request->year);
            } else {
                $material->setEmissionFactor($row->toEmissionFactor($request->year, $material->unit()));
            }

            $this->materialRepository->add($material);
        }

        return new UpdateEmissionFactorsResponse(year: $request->year);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEmissionFactorsRequest;
    }
}
