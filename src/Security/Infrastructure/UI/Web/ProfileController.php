<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web;

use App\Security\Application\UnauthorizedActionException;
use App\Security\Application\User\UpdateProfile\UpdateProfileRequest;
use App\Security\Application\User\UpdateProfile\UpdateProfileService;
use App\Security\Infrastructure\Symfony\Form\ProfileType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Domain\Model\Locale\Locale;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/profile', name: 'profile')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __invoke(
        LoggedUser $loggedUser,
        UpdateProfileService $updateProfile,
        Request $request,
    ): Response {
        $user = $loggedUser();

        $form = $this->createForm(ProfileType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Locale $preferredLocale */
            $preferredLocale = $form->get('preferredLocale')->getData();

            try {
                $updateProfile(new UpdateProfileRequest(
                    userId: $user->id(),
                    preferredLocale: $preferredLocale,
                ), $user);

                $this->addFlash('success', new TranslatableMessage('Tu perfil se ha actualizado correctamente'));

                return $this->redirectToRoute('profile');
            } catch (UnauthorizedActionException $e) {
                throw $this->createAccessDeniedException($e->getMessage());
            } catch (\Exception) {
                $this->addFlash('danger', new TranslatableMessage('Error inesperado. No se ha actualizado tu perfil'));
            }
        }

        return $this->render('@security/profile.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
}
