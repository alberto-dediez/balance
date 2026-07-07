<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CateringItem\Register;

use App\CarbonFootprint\Application\CateringItem\CateringItemAuthorizer;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItem;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemId;
use App\CarbonFootprint\Domain\Model\CateringItem\CateringItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterCateringItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CateringItemRepository $cateringItemRepository,
        private CateringItemAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterCateringItemRequest $request, User $user): RegisterCateringItemResponse
    {
        $id = CateringItemId::generate();

        $this->authorizer->denyUnlessCanDoThat(new CateringItem(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $cateringItem = new CateringItem($id);
        $cateringItem->register(
            $request->name,
            $request->category,
            $request->unit,
            $isDefaultLocale ? null : $locale,
        );

        $this->cateringItemRepository->add($cateringItem);

        return new RegisterCateringItemResponse(
            cateringItemId: $cateringItem->id(),
            name: $cateringItem->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCateringItemRequest;
    }
}
