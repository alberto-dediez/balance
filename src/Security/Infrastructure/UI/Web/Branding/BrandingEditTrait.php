<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Branding;

use App\Security\Application\Branding\Register\RegisterBrandingRequest;
use App\Security\Application\Branding\Register\RegisterBrandingService;
use App\Security\Application\Branding\Update\UpdateBrandingRequest;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Domain\Model\User\User;
use Symfony\Component\Form\FormInterface;

trait BrandingEditTrait
{
    private function brandingOf(
        AccountId $accountId,
        BrandingRepository $repository,
        RegisterBrandingService $registerBranding,
        User $user,
    ): Branding {
        $branding = $repository->findByAccountId($accountId);

        if ($branding instanceof Branding) {
            return $branding;
        }

        $registered = $registerBranding(new RegisterBrandingRequest($accountId), $user);

        $branding = $repository->find($registered->brandingId);

        if (!$branding instanceof Branding) {
            throw $this->createNotFoundException('Branding not found');
        }

        return $branding;
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function submittedThemeSlug(FormInterface $form): ?string
    {
        if (!$form->has('themeSlug')) {
            return null;
        }

        $slug = $form->get('themeSlug')->getData();

        return is_string($slug) ? $slug : null;
    }

    private function updateRequestFrom(Branding $branding): UpdateBrandingRequest
    {
        return new UpdateBrandingRequest(
            brandingId: $branding->id(),
            logoMediaId: $branding->logoMediaId(),
            primary: $branding->primary(),
            secondary: $branding->secondary(),
            success: $branding->success(),
            info: $branding->info(),
            warning: $branding->warning(),
            danger: $branding->danger(),
            light: $branding->light(),
            dark: $branding->dark(),
            bodyColor: $branding->bodyColor(),
            bodyBg: $branding->bodyBg(),
            primaryHover: $branding->primaryHover(),
            primarySubtle: $branding->primarySubtle(),
            secondaryHover: $branding->secondaryHover(),
            secondarySubtle: $branding->secondarySubtle(),
            webFontFamily: $branding->webFontFamily(),
            webFontWeights: $branding->webFontWeights(),
            pdfFontFamily: $branding->pdfFontFamily(),
        );
    }
}
