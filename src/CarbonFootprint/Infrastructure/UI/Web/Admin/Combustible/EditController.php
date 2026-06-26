<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Combustible;

use App\CarbonFootprint\Application\Combustible\Update\EmissionFactorData;
use App\CarbonFootprint\Application\Combustible\Update\UpdateCombustibleRequest;
use App\CarbonFootprint\Application\Combustible\Update\UpdateCombustibleService;
use App\CarbonFootprint\Domain\Model\Combustible\CombustibleRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateCombustibleType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/combustibles/edit/{combustibleId}', name: 'admin_combustibles_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetCombustibleFromIdTrait;

    public function __invoke(
        string $combustibleId,
        CombustibleRepository $combustibleRepository,
        UpdateCombustibleService $updateCombustible,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $combustible = $this->getCombustibleFromId($combustibleId, $combustibleRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $combustible);

        $emissionFactors = [];
        foreach ($combustible->emissionFactors() as $combustibleEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($combustibleEmissionFactor->emissionFactor());
        }

        $combustibleRequest = new UpdateCombustibleRequest(
            combustibleId: $combustible->id(),
            name: $combustible->name(),
            unit: $combustible->unit(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateCombustibleType::class, $combustibleRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateCombustible, $loggedUser) {
                $combustibleRequest = $form->getData();

                $updateCombustible($combustibleRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Combustible actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_combustibles');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/combustibles/edit.html.twig', [
            'combustible' => $combustible,
            'form' => $form->createView(),
        ]);
    }
}
