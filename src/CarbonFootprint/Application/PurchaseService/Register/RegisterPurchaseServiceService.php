<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PurchaseService\Register;

use App\CarbonFootprint\Application\PurchaseService\PurchaseServiceAuthorizer;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseService;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceId;
use App\CarbonFootprint\Domain\Model\PurchaseService\PurchaseServiceRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterPurchaseServiceService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PurchaseServiceRepository $purchaseServiceRepository,
        private PurchaseServiceAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterPurchaseServiceRequest $request, User $user): RegisterPurchaseServiceResponse
    {
        $id = PurchaseServiceId::generate();

        $this->authorizer->denyUnlessCanDoThat(new PurchaseService(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $purchaseService = new PurchaseService($id);
        $purchaseService->register(
            $request->name,
            $request->category,
            $request->unit,
            $isDefaultLocale ? null : $locale,
        );

        $this->purchaseServiceRepository->add($purchaseService);

        return new RegisterPurchaseServiceResponse(
            purchaseServiceId: $purchaseService->id(),
            name: $purchaseService->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterPurchaseServiceRequest;
    }
}
