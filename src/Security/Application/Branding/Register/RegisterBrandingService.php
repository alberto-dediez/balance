<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\Register;

use App\Security\Application\Action;
use App\Security\Application\Branding\BrandingAuthorizer;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingId;
use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RegisterBrandingService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private BrandingRepository $repository, private BrandingAuthorizer $authorizer) {}

    public function __invoke(RegisterBrandingRequest $request, User $user): RegisterBrandingResponse
    {
        $branding = new Branding(BrandingId::generate(), $request->accountId);

        $this->authorizer->denyUnlessCanDoThat($branding, Action::Register, $user);

        $existing = $this->repository->findByAccountId($request->accountId);

        if ($existing instanceof Branding) {
            return new RegisterBrandingResponse($existing->id());
        }

        $branding->register($request->themeSlug);

        $this->repository->add($branding);

        return new RegisterBrandingResponse($branding->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RegisterBrandingRequest;
    }
}
