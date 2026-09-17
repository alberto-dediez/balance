<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup;

use App\CarbonFootprint\Application\EmissionFactorGroup\Remove\RemoveEmissionFactorGroupRequest;
use App\CarbonFootprint\Application\EmissionFactorGroup\Remove\RemoveEmissionFactorGroupService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CsrfProtected;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/emission-factor-groups/remove/{emissionFactorGroupId}', name: 'admin_emission_factor_groups_remove')]
#[CsrfProtected('remove')]
class RemoveController extends AbstractController
{
    use GetEmissionFactorGroupFromIdTrait;

    public function __invoke(
        string $emissionFactorGroupId,
        EmissionFactorGroupRepository $emissionFactorGroupRepository,
        RemoveEmissionFactorGroupService $removeEmissionFactorGroup,
        LoggedUser $loggedUser,
    ): Response {
        $emissionFactorGroup = $this->getEmissionFactorGroupFromId($emissionFactorGroupId, $emissionFactorGroupRepository);

        try {
            $removeEmissionFactorGroup(new RemoveEmissionFactorGroupRequest($emissionFactorGroup->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha eliminado el grupo de coeficientes {emissionFactorGroup}', ['{emissionFactorGroup}' => $emissionFactorGroup->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido eliminar el grupo de coeficientes {emissionFactorGroup}', ['{emissionFactorGroup}' => $emissionFactorGroup->name()]));
        }

        return $this->redirectToRoute('admin_emission_factor_groups');
    }
}
