<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemFilter;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class CreateEmissionFactorsYearService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MerchandisingItemRepository $merchandisingItemRepository,
        private MerchandisingItemAuthorizer $authorizer,
    ) {}

    public function __invoke(CreateEmissionFactorsYearRequest $request, User $user): CreateEmissionFactorsYearResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new MerchandisingItem(id: MerchandisingItemId::generate()), SecurityAction::Edit, $user);

        $targetYear = (int) $request->targetYear;
        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        if ($targetYear > $currentYear) {
            throw new \InvalidArgumentException('Target year cannot be in the future');
        }

        if ($targetYear === $request->sourceYear) {
            throw new \InvalidArgumentException('Target year must differ from the source year');
        }

        $merchandisingItems = $this->merchandisingItemRepository->findByFilter(new MerchandisingItemFilter(limit: 0));

        foreach ($merchandisingItems as $merchandisingItem) {
            if ($merchandisingItem->emissionFactors()->hasForYear($targetYear)) {
                throw EmissionFactorYearAlreadyExistsException::forYear($targetYear);
            }
        }

        foreach ($merchandisingItems as $merchandisingItem) {
            $sourceFactor = $merchandisingItem->emissionFactorFor($request->sourceYear);

            if (null === $sourceFactor) {
                continue;
            }

            $merchandisingItem->setEmissionFactor(
                $sourceFactor->newFactor()->withYear($targetYear),
                $sourceFactor->recycledFactor()->withYear($targetYear),
            );

            $this->merchandisingItemRepository->add($merchandisingItem);
        }

        return new CreateEmissionFactorsYearResponse(targetYear: $targetYear);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CreateEmissionFactorsYearRequest;
    }
}
