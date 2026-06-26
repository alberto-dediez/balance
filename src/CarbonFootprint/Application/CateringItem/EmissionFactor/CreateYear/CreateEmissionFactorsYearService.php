<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\EmissionFactor\CreateYear;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemFilter;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
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
        private CateringItemRepository $cateringItemRepository,
        private CateringItemAuthorizer $authorizer,
    ) {}

    public function __invoke(CreateEmissionFactorsYearRequest $request, User $user): CreateEmissionFactorsYearResponse
    {
        $this->authorizer->denyUnlessCanDoThat(new CateringItem(id: CateringItemId::generate()), SecurityAction::Edit, $user);

        $targetYear = (int) $request->targetYear;
        $currentYear = (int) (new \DateTimeImmutable('NOW'))->format('Y');

        if ($targetYear > $currentYear) {
            throw new \InvalidArgumentException('Target year cannot be in the future');
        }

        if ($targetYear === $request->sourceYear) {
            throw new \InvalidArgumentException('Target year must differ from the source year');
        }

        $cateringItems = $this->cateringItemRepository->findByFilter(new CateringItemFilter(limit: 0));

        foreach ($cateringItems as $cateringItem) {
            if ($cateringItem->emissionFactors()->hasForYear($targetYear)) {
                throw EmissionFactorYearAlreadyExistsException::forYear($targetYear);
            }
        }

        foreach ($cateringItems as $cateringItem) {
            $sourceFactor = $cateringItem->emissionFactorFor($request->sourceYear);

            if (null === $sourceFactor) {
                continue;
            }

            $cateringItem->setEmissionFactor(
                $sourceFactor->newFactor()->withYear($targetYear),
                $sourceFactor->recycledFactor()->withYear($targetYear),
            );

            $this->cateringItemRepository->add($cateringItem);
        }

        return new CreateEmissionFactorsYearResponse(targetYear: $targetYear);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof CreateEmissionFactorsYearRequest;
    }
}
