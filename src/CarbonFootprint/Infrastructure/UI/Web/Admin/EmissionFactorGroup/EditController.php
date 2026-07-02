<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup;

use App\CarbonFootprint\Application\EmissionFactorGroup\Update\UpdateEmissionFactorGroupRequest;
use App\CarbonFootprint\Application\EmissionFactorGroup\Update\UpdateEmissionFactorGroupService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateEmissionFactorGroupType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/emission-factor-groups/edit/{emissionFactorGroupId}', name: 'admin_emission_factor_groups_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetEmissionFactorGroupFromIdTrait;

    public function __invoke(
        string $emissionFactorGroupId,
        EmissionFactorGroupRepository $emissionFactorGroupRepository,
        UpdateEmissionFactorGroupService $updateEmissionFactorGroup,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $emissionFactorGroup = $this->getEmissionFactorGroupFromId($emissionFactorGroupId, $emissionFactorGroupRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $emissionFactorGroup);

        $emissionFactorGroupRequest = new UpdateEmissionFactorGroupRequest(
            emissionFactorGroupId: $emissionFactorGroup->id(),
            name: $emissionFactorGroup->name(),
            gwpCo2: $emissionFactorGroup->gwp()->gwpCo2(),
            gwpCh4: $emissionFactorGroup->gwp()->gwpCh4(),
            gwpN2O: $emissionFactorGroup->gwp()->gwpN2O(),
        );

        try {
            $form = $this->createForm(UpdateEmissionFactorGroupType::class, $emissionFactorGroupRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateEmissionFactorGroup, $loggedUser) {
                $emissionFactorGroupRequest = $form->getData();

                $updateEmissionFactorGroup($emissionFactorGroupRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Grupo de coeficientes actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_emission_factor_groups');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/emission_factor_groups/edit.html.twig', [
            'emissionFactorGroup' => $emissionFactorGroup,
            'form' => $form->createView(),
        ]);
    }
}
