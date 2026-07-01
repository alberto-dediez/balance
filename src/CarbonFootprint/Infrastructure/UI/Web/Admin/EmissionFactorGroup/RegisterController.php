<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EmissionFactorGroup;

use App\CarbonFootprint\Application\EmissionFactorGroup\Register\RegisterEmissionFactorGroupService;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroup;
use App\CarbonFootprint\Domain\Model\EmissionFactorGroup\EmissionFactorGroupId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterEmissionFactorGroupType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/emission-factor-groups/register', name: 'admin_emission_factor_groups_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterEmissionFactorGroupService $registerEmissionFactorGroup, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new EmissionFactorGroup(EmissionFactorGroupId::generate()));

        $form = $this->createForm(RegisterEmissionFactorGroupType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerEmissionFactorGroup, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerEmissionFactorGroup($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Grupo de coeficientes creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_emission_factor_groups');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/emission_factor_groups/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
