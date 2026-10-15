<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\UseProfile;

use App\CarbonFootprint\Application\UseProfile\Update\EmissionFactorData;
use App\CarbonFootprint\Application\UseProfile\Update\UpdateUseProfileRequest;
use App\CarbonFootprint\Application\UseProfile\Update\UpdateUseProfileService;
use App\CarbonFootprint\Domain\Model\UseProfile\UseProfileRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateUseProfileType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/use-profiles/edit/{useProfileId}', name: 'admin_use_profiles_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetUseProfileFromIdTrait;

    public function __invoke(
        string $useProfileId,
        UseProfileRepository $useProfileRepository,
        UpdateUseProfileService $updateUseProfile,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $useProfile = $this->getUseProfileFromId($useProfileId, $useProfileRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $useProfile);

        $emissionFactors = [];
        foreach ($useProfile->emissionFactors() as $useProfileEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromUseProfileEmissionFactor($useProfileEmissionFactor);
        }

        $useProfileRequest = new UpdateUseProfileRequest(
            useProfileId: $useProfile->id(),
            name: $useProfile->name(),
            unit: $useProfile->unit(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateUseProfileType::class, $useProfileRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateUseProfile, $loggedUser) {
                $useProfileRequest = $form->getData();

                $updateUseProfile($useProfileRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Perfil de uso actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_use_profiles');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/use_profiles/edit.html.twig', [
            'useProfile' => $useProfile,
            'form' => $form->createView(),
        ]);
    }
}
