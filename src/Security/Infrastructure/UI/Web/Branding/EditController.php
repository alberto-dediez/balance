<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Branding;

use App\Security\Application\Branding\BrandingAuthorizer;
use App\Security\Application\Branding\ChangeTheme\ChangeBrandingThemeRequest;
use App\Security\Application\Branding\ChangeTheme\ChangeBrandingThemeService;
use App\Security\Application\Branding\Register\RegisterBrandingService;
use App\Security\Application\Branding\Update\UpdateBrandingService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Infrastructure\Symfony\Form\UpdateBrandingType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Model\Theme\ThemeRegistry;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/branding', name: 'branding_edit')]
#[IsGranted('ROLE_OWNER')]
class EditController extends AbstractController
{
    use BrandingEditTrait;
    use EditTrait;

    public function __invoke(
        BrandingRepository $repository,
        RegisterBrandingService $registerBranding,
        UpdateBrandingService $updateBranding,
        ChangeBrandingThemeService $changeTheme,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        BrandingAuthorizer $authorizer,
        BrandingPalette $palette,
        ThemeRegistry $themeRegistry,
        ContrastWarnings $contrastWarningsFor,
        Request $request,
    ): Response {
        $user = $loggedUser();
        $accountId = $loggedAccount->currentAccountId();

        if (!$accountId instanceof AccountId) {
            throw $this->createNotFoundException('El usuario no pertenece a ninguna cuenta.');
        }

        try {
            $branding = $this->brandingOf($accountId, $repository, $registerBranding, $user);

            $form = $this->createForm(UpdateBrandingType::class, $this->updateRequestFrom($branding), [
                'with_theme' => $authorizer->canEditTheme($branding, $user),
                'theme_slug' => $branding->themeSlug(),
            ]);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($branding, $changeTheme, $updateBranding, $user): void {
                $themeSlug = $this->submittedThemeSlug($form);

                if (null !== $themeSlug) {
                    $changeTheme(new ChangeBrandingThemeRequest($branding->id(), $themeSlug), $user);
                }

                $updateBranding($form->getData(), $user);

                $this->addFlash('success', new TranslatableMessage('Personalización actualizada con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('home');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }


        $current = $form->getData();
        $theme = $themeRegistry->get($branding->themeSlug());

        $contrastWarnings = $contrastWarningsFor->for($palette->resolveOverrides(
            defaults: $theme->defaults(),
            themeSlug: $theme->slug(),
            primary: $current->primary,
            secondary: $current->secondary,
            success: $current->success,
            info: $current->info,
            warning: $current->warning,
            danger: $current->danger,
            light: $current->light,
            dark: $current->dark,
            bodyColor: $current->bodyColor,
            bodyBg: $current->bodyBg,
            primaryHover: $current->primaryHover,
            primarySubtle: $current->primarySubtle,
            secondaryHover: $current->secondaryHover,
            secondarySubtle: $current->secondarySubtle,
            logoMediaId: $current->logoMediaId,
            webFontFamily: $current->webFontFamily,
            webFontWeights: $current->webFontWeights,
            pdfFontFamily: $current->pdfFontFamily,
            branded: true,
        ));

        return $this->render('@security/branding/edit.html.twig', [
            'branding' => $branding,
            'form' => $form->createView(),
            'contrastWarnings' => $contrastWarnings,
        ]);
    }
}
