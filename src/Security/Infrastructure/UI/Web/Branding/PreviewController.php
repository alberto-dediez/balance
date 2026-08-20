<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Branding;

use App\Security\Application\Action;
use App\Security\Application\Branding\BrandingAuthorizer;
use App\Security\Application\Branding\Update\UpdateBrandingRequest;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Branding\Branding;
use App\Security\Domain\Model\Branding\BrandingId;
use App\Security\Domain\Model\Branding\BrandingPalette;
use App\Security\Domain\Model\Branding\BrandingRepository;
use App\Security\Infrastructure\Symfony\Form\UpdateBrandingType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Model\Theme\ThemeRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/branding/preview/{brandingId}', name: 'branding_preview')]
#[IsGranted('ROLE_USER')]
class PreviewController extends AbstractController
{
    use BrandingEditTrait;

    public function __invoke(
        string $brandingId,
        BrandingRepository $repository,
        BrandingPalette $palette,
        ThemeRegistry $themeRegistry,
        BrandingContext $context,
        BrandingAuthorizer $authorizer,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $branding = $repository->find(new BrandingId($brandingId));

        if (!$branding instanceof Branding) {
            throw $this->createNotFoundException('Branding not found');
        }

        try {
            $authorizer->denyUnlessCanDoThat($branding, Action::View, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        $form = $this->createForm(UpdateBrandingType::class, $this->updateRequestFrom($branding));
        $form->handleRequest($request);

        /** @var UpdateBrandingRequest $preview */
        $preview = $form->getData();

        $theme = $themeRegistry->get($branding->themeSlug());

        $context->useTokens($palette->resolveOverrides(
            defaults: $theme->defaults(),
            themeSlug: $theme->slug(),
            primary: $preview->primary,
            secondary: $preview->secondary,
            success: $preview->success,
            info: $preview->info,
            warning: $preview->warning,
            danger: $preview->danger,
            light: $preview->light,
            dark: $preview->dark,
            bodyColor: $preview->bodyColor,
            bodyBg: $preview->bodyBg,
            primaryHover: $preview->primaryHover,
            primarySubtle: $preview->primarySubtle,
            secondaryHover: $preview->secondaryHover,
            secondarySubtle: $preview->secondarySubtle,
            logoMediaId: $preview->logoMediaId,
            webFontFamily: $preview->webFontFamily,
            webFontWeights: $preview->webFontWeights,
            pdfFontFamily: $preview->pdfFontFamily,
            branded: true,
        ));

        $response = $this->render('@security/branding/preview.html.twig');

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
