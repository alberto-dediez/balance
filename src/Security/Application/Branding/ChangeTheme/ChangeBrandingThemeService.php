<?php

declare(strict_types=1);

namespace App\Security\Application\Branding\ChangeTheme;

use App\Security\Application\Branding\BrandingAuthorizer;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Theme\ThemeRegistry;

class ChangeBrandingThemeService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private BrandingRepository $repository,
        private BrandingAuthorizer $authorizer,
        private ThemeRegistry $themeRegistry,
    ) {}

    public function __invoke(ChangeBrandingThemeRequest $request, User $user): ChangeBrandingThemeResponse
    {
        $branding = $this->repository->find($request->brandingId);

        if (!$branding instanceof Branding) {
            throw new \InvalidArgumentException('Branding not found');
        }

        $this->authorizer->denyUnlessCanEditTheme($branding, $user);

        $theme = $this->themeRegistry->get($request->themeSlug);

        if ($theme->slug() !== $branding->themeSlug()) {
            $branding->changeTheme($theme->slug());

            $this->repository->add($branding);
        }

        return new ChangeBrandingThemeResponse($branding->id(), $branding->themeSlug());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ChangeBrandingThemeRequest;
    }
}
