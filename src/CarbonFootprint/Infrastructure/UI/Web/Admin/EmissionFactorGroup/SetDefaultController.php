<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup;

use App\CarbonFootprint\Application\EmissionFactorGroup\SetDefault\SetDefaultEmissionFactorGroupRequest;
use App\CarbonFootprint\Application\EmissionFactorGroup\SetDefault\SetDefaultEmissionFactorGroupService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupRepository;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/emission-factor-groups/set-default/{emissionFactorGroupId}', name: 'admin_emission_factor_groups_set_default')]
class SetDefaultController extends AbstractController
{
    use GetEmissionFactorGroupFromIdTrait;

    public function __invoke(
        string $emissionFactorGroupId,
        EmissionFactorGroupRepository $emissionFactorGroupRepository,
        SetDefaultEmissionFactorGroupService $setDefaultEmissionFactorGroup,
        LoggedUser $loggedUser,
    ): Response {
        $emissionFactorGroup = $this->getEmissionFactorGroupFromId($emissionFactorGroupId, $emissionFactorGroupRepository);

        try {
            $setDefaultEmissionFactorGroup(new SetDefaultEmissionFactorGroupRequest($emissionFactorGroup->id()), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Se ha marcado {emissionFactorGroup} como grupo de coeficientes por defecto', ['{emissionFactorGroup}' => $emissionFactorGroup->name()]));
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('danger', new TranslatableMessage('No se ha podido marcar {emissionFactorGroup} como grupo de coeficientes por defecto', ['{emissionFactorGroup}' => $emissionFactorGroup->name()]));
        }

        return $this->redirectToRoute('admin_emission_factor_groups');
    }
}
