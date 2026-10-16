<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\EconomicSector;

use App\CarbonFootprint\Application\EconomicSector\Register\RegisterEconomicSectorService;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSector;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorCodeAlreadyExistsException;
use App\CarbonFootprint\Domain\Model\EconomicSector\EconomicSectorId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterEconomicSectorType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/economic-sectors/register', name: 'admin_economic_sectors_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterEconomicSectorService $registerEconomicSector, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new EconomicSector(EconomicSectorId::generate()));

        $form = $this->createForm(RegisterEconomicSectorType::class);

        try {
            $economicSectorId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerEconomicSector, $loggedUser, &$economicSectorId) {
                $registerRequest = $form->getData();

                try {
                    $response = $registerEconomicSector($registerRequest, $loggedUser());
                } catch (EconomicSectorCodeAlreadyExistsException) {
                    $form->get('classificationCode')->addError(new FormError((string) new TranslatableMessage('Ya existe un sector económico con ese código')));

                    return false;
                }

                $economicSectorId = $response->economicSectorId;

                $this->addFlash('success', new TranslatableMessage('Sector económico creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($economicSectorId instanceof EconomicSectorId) {
                    return $this->redirectToRoute('admin_economic_sectors_edit', ['economicSectorId' => $economicSectorId]);
                }

                return $this->redirectToRoute('admin_economic_sectors');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/economic_sectors/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
