<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\MerchandisingItem\Register;

use App\CarbonFootprint\Application\MerchandisingItem\MerchandisingItemAuthorizer;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItem;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemId;
use App\CarbonFootprint\Domain\Model\MerchandisingItem\MerchandisingItemRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterMerchandisingItemService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private MerchandisingItemRepository $merchandisingItemRepository,
        private MerchandisingItemAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterMerchandisingItemRequest $request, User $user): RegisterMerchandisingItemResponse
    {
        $id = MerchandisingItemId::generate();

        $this->authorizer->denyUnlessCanDoThat(new MerchandisingItem(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $merchandisingItem = new MerchandisingItem($id);
        $merchandisingItem->register(
            $request->name,
            $request->category,
            $request->unit,
            $request->unitWeight,
            $isDefaultLocale ? null : $locale,
        );

        $this->merchandisingItemRepository->add($merchandisingItem);

        return new RegisterMerchandisingItemResponse(
            merchandisingItemId: $merchandisingItem->id(),
            name: $merchandisingItem->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterMerchandisingItemRequest;
    }
}
