<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\UI\Web\Surplus;

use App\FoodLoss\Application\Surplus\Register\RegisterSurplusRequest;
use App\FoodLoss\Application\Surplus\Register\RegisterSurplusService;
use App\FoodLoss\Domain\Model\Incidence\IncidenceId;
use App\FoodLoss\Domain\Model\Surplus\Surplus;
use App\FoodLoss\Domain\Model\Surplus\SurplusId;
use App\FoodLoss\Infrastructure\Symfony\Form\RegisterSurplusType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/surpluses/register', name: 'surpluses_register')]
class RegisterController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(RegisterSurplusService $registerSurplus, LoggedUser $loggedUser, LoggedAccount $loggedAccount, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Surplus(SurplusId::generate(), accountId: $loggedAccount()?->id() ?? AccountId::generate()));

        $registerRequest = new RegisterSurplusRequest();
        $incidenceIdParam = $request->query->get('incidenceId');
        if (null !== $incidenceIdParam && '' !== $incidenceIdParam) {
            $registerRequest->incidenceId = new IncidenceId((string) $incidenceIdParam);
        }

        $form = $this->createForm(RegisterSurplusType::class, $registerRequest);

        $surplusId = null;

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($registerSurplus, $loggedUser, $loggedAccount, &$surplusId): true {
                $registerRequest = $form->getData();
                $registerRequest->accountId = $loggedAccount()?->id();
                $registerRequest->registeredBy = $loggedUser()->id();

                $response = $registerSurplus($registerRequest, $loggedUser());

                $surplusId = (string) $response->surplusId;

                $this->addFlash('success', new TranslatableMessage('Excedente registrado correctamente: {date}', ['{date}' => $response->date->format('d/m/Y')]));

                return true;
            });

            if ($needsRedirect && null !== $surplusId) {
                return $this->redirectToRoute('surpluses_prevention', ['surplusId' => $surplusId]);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@food_loss/surpluses/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
