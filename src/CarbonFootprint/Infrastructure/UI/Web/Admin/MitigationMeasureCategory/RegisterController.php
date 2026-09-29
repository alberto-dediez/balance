<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\MitigationMeasureCategory;

use App\CarbonFootprint\Application\MitigationMeasureCategory\Register\RegisterMitigationMeasureCategoryService;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategory;
use App\CarbonFootprint\Domain\Model\MitigationMeasureCategory\MitigationMeasureCategoryId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterMitigationMeasureCategoryType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/mitigation-measure-categories/register', name: 'admin_mitigation_measure_categories_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterMitigationMeasureCategoryService $registerMitigationMeasureCategory, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new MitigationMeasureCategory(MitigationMeasureCategoryId::generate()));

        $form = $this->createForm(RegisterMitigationMeasureCategoryType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMitigationMeasureCategory, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerMitigationMeasureCategory($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Categoría de medidas creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_mitigation_measure_categories');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/mitigation_measure_categories/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
