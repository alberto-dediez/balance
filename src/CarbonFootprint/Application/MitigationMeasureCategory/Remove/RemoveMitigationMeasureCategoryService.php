<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasureCategory\Remove;

use App\CarbonFootprint\Application\MitigationMeasureCategory\MitigationMeasureCategoryAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureFilter;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureRepository;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveMitigationMeasureCategoryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationMeasureCategoryRepository $repository,
        private MitigationMeasureRepository $mitigationMeasureRepository,
        private MitigationMeasureCategoryAuthorizer $authorizer,
    ) {}

    public function __invoke(RemoveMitigationMeasureCategoryRequest $request, User $user): RemoveMitigationMeasureCategoryResponse
    {
        $mitigationMeasureCategory = $this->repository->find($request->mitigationMeasureCategoryId);

        if (null == $mitigationMeasureCategory) {
            throw new \InvalidArgumentException('Mitigation measure category not found');
        }

        $this->authorizer->denyUnlessCanDoThat($mitigationMeasureCategory, SecurityAction::Remove, $user);

        if ($this->mitigationMeasureRepository->totalResultsByFilter(new MitigationMeasureFilter(categoryId: $mitigationMeasureCategory->id())) > 0) {
            throw new \InvalidArgumentException('Mitigation measure category is in use');
        }

        $mitigationMeasureCategory->remove();

        $this->repository->add($mitigationMeasureCategory);

        return new RemoveMitigationMeasureCategoryResponse(
            mitigationMeasureCategoryId: $mitigationMeasureCategory->id(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveMitigationMeasureCategoryRequest;
    }
}
