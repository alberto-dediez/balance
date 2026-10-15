<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\UseProfile;

use App\CarbonFootprint\Application\UseProfile\Remove\RemoveUseProfileRequest;
use App\CarbonFootprint\Application\UseProfile\Remove\RemoveUseProfileService;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/use-profiles/remove/{useProfileId}', name: 'admin_use_profiles_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetUseProfileFromIdTrait;

    public function __invoke(
        string $useProfileId,
        UseProfileRepository $useProfileRepository,
        RemoveUseProfileService $removeUseProfile,
        LoggedUser $loggedUser,
    ): Response {
        $useProfile = $this->getUseProfileFromId($useProfileId, $useProfileRepository);

        try {
            $removeUseProfile(new RemoveUseProfileRequest($useProfile->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el perfil de uso {useProfile}', ['{useProfile}' => $useProfile->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el perfil de uso {useProfile}', ['{useProfile}' => $useProfile->name()]));
        }

        return $this->redirectToRoute('admin_use_profiles');
    }
}
