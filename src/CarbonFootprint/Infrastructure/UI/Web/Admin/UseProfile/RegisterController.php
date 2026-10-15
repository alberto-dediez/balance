<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\UseProfile;

use App\CarbonFootprint\Application\UseProfile\Register\RegisterUseProfileService;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfile;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterUseProfileType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/use-profiles/register', name: 'admin_use_profiles_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterUseProfileService $registerUseProfile, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new UseProfile(UseProfileId::generate()));

        $form = $this->createForm(RegisterUseProfileType::class);

        try {
            $useProfileId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerUseProfile, $loggedUser, &$useProfileId) {
                $registerRequest = $form->getData();

                $response = $registerUseProfile($registerRequest, $loggedUser());

                $useProfileId = $response->useProfileId;

                $this->addFlash('success', new TranslatableMessage('Perfil de uso creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($useProfileId instanceof UseProfileId) {
                    return $this->redirectToRoute('admin_use_profiles_edit', ['useProfileId' => $useProfileId]);
                }

                return $this->redirectToRoute('admin_use_profiles');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/use_profiles/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
