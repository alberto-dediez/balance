<?php

namespace App\FoodLoss\Application\ServiceCategory\Update;

use InvalidArgumentException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;

class UpdateServiceCategoryService implements CommandHandler
{
    use RegisterHandlerTrait, CommandInvokeHandlerTrait;

    public function __construct(
        private ServiceCategoryRepository $serviceCategoryRepository,
        private LocaleGetter $localeGetter,
        private ServiceCategoryAuthorizer $authorizer
    ) {

    }

    public function __invoke(UpdateServiceCategoryRequest $request, User $user): UpdateServiceCategoryResponse
    {
        $serviceCategory = $this->serviceCategoryRepository->find($request->serviceCategoryId);
        
        if (null == $serviceCategory) {
            throw new InvalidArgumentException("Service category not found");
        }

        $this->authorizer->denyUnlessCanDoThat($serviceCategory, Action::Edit, $user);

        $locale = $this->localeGetter->locale();
        $isDefaultLocale = $locale === $this->localeGetter->default();

        $serviceCategory->update(
            name: $request->name,
            locale: $isDefaultLocale ? null : $locale
        );

        $this->serviceCategoryRepository->add($serviceCategory);

        return new UpdateServiceCategoryResponse(
            serviceCategoryId: $serviceCategory->id(),
            name: $serviceCategory->name()
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateServiceCategoryRequest;
    }
}