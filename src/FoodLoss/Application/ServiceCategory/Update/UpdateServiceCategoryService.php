<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ServiceCategory\Update;

use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdateServiceCategoryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ServiceCategoryRepository $serviceCategoryRepository,
        private ServiceCategoryAuthorizer $authorizer,
    ) {}

    public function __invoke(UpdateServiceCategoryRequest $request, User $user): UpdateServiceCategoryResponse
    {
        $serviceCategory = $this->serviceCategoryRepository->find($request->serviceCategoryId);

        if (null == $serviceCategory) {
            throw new \InvalidArgumentException('Service category not found');
        }

        $this->authorizer->denyUnlessCanDoThat($serviceCategory, Action::Edit, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = $locale === LocaleGetter::default();

        $serviceCategory->update(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale,
        );

        $this->serviceCategoryRepository->add($serviceCategory);

        return new UpdateServiceCategoryResponse(
            serviceCategoryId: $serviceCategory->id(),
            name: $serviceCategory->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateServiceCategoryRequest;
    }
}
