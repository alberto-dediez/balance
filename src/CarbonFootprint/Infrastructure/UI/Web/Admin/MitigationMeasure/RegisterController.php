<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasure;

use App\CarbonFootprint\Application\MitigationMeasure\Register\RegisterMitigationMeasureService;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasure;
use App\CarbonFootprint\Domain\Model\MitigationMeasure\MitigationMeasureId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterMitigationMeasureType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/mitigation-measures/register', name: 'admin_mitigation_measures_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterMitigationMeasureService $registerMitigationMeasure, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new MitigationMeasure(MitigationMeasureId::generate()));

        $form = $this->createForm(RegisterMitigationMeasureType::class);

        try {
            $mitigationMeasureId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMitigationMeasure, $loggedUser, &$mitigationMeasureId) {
                $registerRequest = $form->getData();

                $response = $registerMitigationMeasure($registerRequest, $loggedUser());

                $mitigationMeasureId = $response->mitigationMeasureId;

                $this->addFlash('success', new TranslatableMessage('Medida creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($mitigationMeasureId instanceof MitigationMeasureId) {
                    return $this->redirectToRoute('admin_mitigation_measures_edit', ['mitigationMeasureId' => $mitigationMeasureId]);
                }

                return $this->redirectToRoute('admin_mitigation_measures');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/mitigation_measures/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
