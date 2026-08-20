<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\Remove;

use App\Security\Application\Action;
use App\Security\Application\Branding\BrandingAuthorizer;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class RemoveBrandingService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private BrandingRepository $repository, private BrandingAuthorizer $authorizer) {}

    public function __invoke(RemoveBrandingRequest $request, User $user): RemoveBrandingResponse
    {
        $branding = $this->repository->find($request->brandingId);

        if (!$branding instanceof Branding) {
            throw new \InvalidArgumentException('Branding not found');
        }

        $this->authorizer->denyUnlessCanDoThat($branding, Action::Remove, $user);

        $branding->remove();

        $this->repository->add($branding);

        return new RemoveBrandingResponse($branding->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof RemoveBrandingRequest;
    }
}
