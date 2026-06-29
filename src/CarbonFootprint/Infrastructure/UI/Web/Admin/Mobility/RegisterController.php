<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\Mobility;

use App\CarbonFootprint\Application\Mobility\Register\RegisterMobilityService;
use App\CarbonFootprint\Domain\Model\Mobility\Mobility;
use App\CarbonFootprint\Domain\Model\Mobility\MobilityId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterMobilityType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/mobilities/register', name: 'admin_mobilities_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterMobilityService $registerMobility, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Mobility(MobilityId::generate()));

        $form = $this->createForm(RegisterMobilityType::class);

        try {
            $mobilityId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerMobility, $loggedUser, &$mobilityId) {
                $registerRequest = $form->getData();

                $response = $registerMobility($registerRequest, $loggedUser());

                $mobilityId = $response->mobilityId;

                $this->addFlash('success', new TranslatableMessage('Movilidad creada correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($mobilityId instanceof MobilityId) {
                    return $this->redirectToRoute('admin_mobilities_edit', ['mobilityId' => $mobilityId]);
                }

                return $this->redirectToRoute('admin_mobilities');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/mobilities/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
