<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\CriticalPoint;

use App\FoodLoss\Application\CriticalPoint\Register\RegisterCriticalPointService;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPoint;
use App\FoodLoss\Domain\Model\CriticalPoint\CriticalPointId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterCriticalPointType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/critical-points/register', name: 'critical_points_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterCriticalPointService $registerCriticalPoint, LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new CriticalPoint(CriticalPointId::generate()));

        $form = $this->createForm(RegisterCriticalPointType::class);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerCriticalPoint, $loggedUser) {
                $registerRequest = $form->getData();

                $response = $registerCriticalPoint($registerRequest, $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Punto crítico creado correctamente: {name}', ['{name}' => $response->name]));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('critical_points');
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->render('@food_loss/critical_points/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
