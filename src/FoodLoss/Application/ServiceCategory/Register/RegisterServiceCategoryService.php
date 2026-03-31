<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\ServiceCategory\Register;

use App\FoodLoss\Application\ServiceCategory\ServiceCategoryAuthorizer;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategory;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryId;
use App\FoodLoss\Domain\Model\ServiceCategory\ServiceCategoryRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterServiceCategoryService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ServiceCategoryRepository $serviceCategoryRepository,
        private ServiceCategoryAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterServiceCategoryRequest $request, User $user): RegisterServiceCategoryResponse
    {
        $id = ServiceCategoryId::generate();

        $this->authorizer->denyUnlessCanDoThat(new ServiceCategory(id: $id), Action::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $serviceCategory = new ServiceCategory($id);
        $serviceCategory->register(
            $request->name,
            $isDefaultLocale ? null : $locale,
        );

        $this->serviceCategoryRepository->add($serviceCategory);

        return new RegisterServiceCategoryResponse(
            serviceCategoryId: $serviceCategory->id(),
            name: $serviceCategory->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterServiceCategoryRequest;
    }
}
