<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MitigationMeasureCategory\Update;

use App\CarbonFootprint\Application\MitigationMeasureCategory\MitigationMeasureCategoryAuthorizer;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateMitigationMeasureCategoryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MitigationMeasureCategoryRepository $mitigationMeasureCategoryRepository,
        private MitigationMeasureCategoryAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateMitigationMeasureCategoryRequest $request, User $user): UpdateMitigationMeasureCategoryResponse
    {
        $mitigationMeasureCategory = $this->mitigationMeasureCategoryRepository->find($request->mitigationMeasureCategoryId);

        if (null == $mitigationMeasureCategory) {
            throw new \InvalidArgumentException('Mitigation measure category not found');
        }

        $this->authorizer->denyUnlessCanDoThat($mitigationMeasureCategory, SecurityAction::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $mitigationMeasureCategory->update(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->mitigationMeasureCategoryRepository->add($mitigationMeasureCategory);

        return new UpdateMitigationMeasureCategoryResponse(
            mitigationMeasureCategoryId: $mitigationMeasureCategory->id(),
            name: $mitigationMeasureCategory->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateMitigationMeasureCategoryRequest;
    }
}
