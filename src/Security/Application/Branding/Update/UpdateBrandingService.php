<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\Update;

use App\Security\Application\Action;
use App\Security\Application\Branding\BrandingAuthorizer;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class UpdateBrandingService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(private BrandingRepository $repository, private BrandingAuthorizer $authorizer) {}

    public function __invoke(UpdateBrandingRequest $request, User $user): UpdateBrandingResponse
    {
        $branding = $this->repository->find($request->brandingId);

        if (!$branding instanceof Branding) {
            throw new \InvalidArgumentException('Branding not found');
        }

        $this->authorizer->denyUnlessCanDoThat($branding, Action::Edit, $user);

        $branding->change(
            logoMediaId: $request->logoMediaId,
            primary: $request->primary,
            secondary: $request->secondary,
            success: $request->success,
            info: $request->info,
            warning: $request->warning,
            danger: $request->danger,
            light: $request->light,
            dark: $request->dark,
            bodyColor: $request->bodyColor,
            bodyBg: $request->bodyBg,
            primaryHover: $request->primaryHover,
            primarySubtle: $request->primarySubtle,
            secondaryHover: $request->secondaryHover,
            secondarySubtle: $request->secondarySubtle,
            webFontFamily: $request->webFontFamily,
            webFontWeights: $request->webFontWeights,
            pdfFontFamily: $request->pdfFontFamily,
        );

        $this->repository->add($branding);

        return new UpdateBrandingResponse($branding->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdateBrandingRequest;
    }
}
