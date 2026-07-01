<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\Update;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
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
        private MerchandisingItemRepository $merchandisingItemRepository,
        private MerchandisingItemAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateEmissionFactorsRequest $request, User $user): UpdateEmissionFactorsResponse
    {
        foreach ($request->rows as $row) {
            $merchandisingItem = $this->merchandisingItemRepository->find($row->merchandisingItemId());

            if (null === $merchandisingItem) {
                continue;
            }

            $this->authorizer->denyUnlessCanDoThat($merchandisingItem, SecurityAction::Edit, $user);

            if ($row->isEmpty()) {
                $merchandisingItem->removeEmissionFactor($request->year, EmissionFactorGroupId::default());
            } else {
                $merchandisingItem->setEmissionFactor(
                    $row->newEmissionFactor($request->year, $merchandisingItem->unit()),
                    $row->recycledEmissionFactor($request->year, $merchandisingItem->unit()),
                );
            }

            $this->merchandisingItemRepository->add($merchandisingItem);
        }

        return new UpdateEmissionFactorsResponse(year: $request->year);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateEmissionFactorsRequest;
    }
}
