<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\UseProfile\Register;

use App\CarbonFootprint\Application\UseProfile\UseProfileAuthorizer;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class RegisterUseProfileService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private UseProfileRepository $useProfileRepository,
        private UseProfileAuthorizer $authorizer,
    ) {}

    public function __invoke(RegisterUseProfileRequest $request, User $user): RegisterUseProfileResponse
    {
        $id = UseProfileId::generate();

        $this->authorizer->denyUnlessCanDoThat(new UseProfile(id: $id), SecurityAction::List, $user);

        $locale = LocaleGetter::locale();
        $isDefaultLocale = LocaleGetter::default() === $locale;

        $useProfile = new UseProfile($id);
        $useProfile->register(
            $request->name,
            $request->unit,
            $isDefaultLocale ? null : $locale,
        );

        $this->useProfileRepository->add($useProfile);

        return new RegisterUseProfileResponse(
            useProfileId: $useProfile->id(),
            name: $useProfile->name(),
        );
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterUseProfileRequest;
    }
}
