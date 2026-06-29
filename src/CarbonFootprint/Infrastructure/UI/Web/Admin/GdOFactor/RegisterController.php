<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\Admin\GdOFactor;

use App\CarbonFootprint\Application\GdOFactor\Register\RegisterGdOFactorService;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactor;
use App\CarbonFootprint\Domain\Model\GdOFactor\GdOFactorId;
use App\CarbonFootprint\Infrastructure\Symfony\Form\RegisterGdOFactorType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/gdo-factors/register', name: 'admin_gdo_factors_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterGdOFactorService $registerGdOFactor, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new GdOFactor(GdOFactorId::generate()));

        $form = $this->createForm(RegisterGdOFactorType::class);

        try {
            $gdOFactorId = null;

            $needsRedirect = $this->register($request, $form, function ($form) use ($registerGdOFactor, $loggedUser, &$gdOFactorId) {
                $registerRequest = $form->getData();

                $response = $registerGdOFactor($registerRequest, $loggedUser());

                $gdOFactorId = $response->gdOFactorId;

                $this->addFlash('success', new TranslatableMessage('Factor GdO creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                if ($gdOFactorId instanceof GdOFactorId) {
                    return $this->redirectToRoute('admin_gdo_factors_edit', ['gdOFactorId' => $gdOFactorId]);
                }

                return $this->redirectToRoute('admin_gdo_factors');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@carbon_footprint/admin/gdo_factors/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
