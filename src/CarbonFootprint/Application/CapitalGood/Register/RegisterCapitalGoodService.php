<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\CapitalGood\Register;

use App\CarbonFootprint\Application\CapitalGood\CapitalGoodAuthorizer;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGood;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodId;
use App\CarbonFootprint\Domain\Model\CapitalGood\CapitalGoodRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterCapitalGoodService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private CapitalGoodRepository $capitalGoodRepository,
        private CapitalGoodAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterCapitalGoodRequest $request, User $user): RegisterCapitalGoodResponse
    {
        $id = CapitalGoodId::generate();

        $this->authorizer->denyUnlessCanDoThat(new CapitalGood(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $capitalGood = new CapitalGood($id);
        $capitalGood->register(
            $request->name,
            $request->category,
            $request->unit,
            $isDefaultLocale ? null : $locale,
        );

        $this->capitalGoodRepository->add($capitalGood);

        return new RegisterCapitalGoodResponse(
            capitalGoodId: $capitalGood->id(),
            name: $capitalGood->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterCapitalGoodRequest;
    }
}
