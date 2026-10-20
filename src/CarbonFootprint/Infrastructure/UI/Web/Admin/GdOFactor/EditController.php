<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\GdOFactor;

use App\CarbonFootprint\Application\GdOFactor\Update\EmissionFactorData;
use App\CarbonFootprint\Application\GdOFactor\Update\UpdateGdOFactorRequest;
use App\CarbonFootprint\Application\GdOFactor\Update\UpdateGdOFactorService;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorRepository;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateGdOFactorType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\EditTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/gdo-factors/edit/{gdOFactorId}', name: 'admin_gdo_factors_edit')]
class EditController extends AbstractController
{
    use EditTrait;
    use GetGdOFactorFromIdTrait;

    public function __invoke(
        string $gdOFactorId,
        GdOFactorRepository $gdOFactorRepository,
        UpdateGdOFactorService $updateGdOFactor,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        $gdOFactor = $this->getGdOFactorFromId($gdOFactorId, $gdOFactorRepository);

        $this->denyAccessUnlessGranted(SecurityAction::Edit->value, $gdOFactor);

        $emissionFactors = [];
        foreach ($gdOFactor->emissionFactors() as $gdOFactorEmissionFactor) {
            $emissionFactors[] = EmissionFactorData::fromEmissionFactor($gdOFactorEmissionFactor);
        }

        $gdOFactorRequest = new UpdateGdOFactorRequest(
            gdOFactorId: $gdOFactor->id(),
            name: $gdOFactor->name(),
            emissionFactors: $emissionFactors,
        );

        try {
            $form = $this->createForm(UpdateGdOFactorType::class, $gdOFactorRequest);

            $needsRedirect = $this->edit($request, $form, function ($form) use ($updateGdOFactor, $loggedUser) {
                $gdOFactorRequest = $form->getData();

                $updateGdOFactor($gdOFactorRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Factor GdO actualizado con éxito'));
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('admin_gdo_factors');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/gdo_factors/edit.html.twig', [
            'gdOFactor' => $gdOFactor,
            'form' => $form->createView(),
        ]);
    }
}
