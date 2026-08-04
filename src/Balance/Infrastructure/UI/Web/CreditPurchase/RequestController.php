<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\CreditPurchase;

use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseRequest;
use App\Balance\Application\CreditPurchase\Request\RequestCreditPurchaseService;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Infrastructure\Symfony\Form\RequestCreditPurchaseType;
use App\Balance\Infrastructure\UI\Web\Project\GetProjectFromIdTrait;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/projects/{projectId}/credits/request', name: 'credit_purchases_request')]
class RequestController extends AbstractController
{
    use GetProjectFromIdTrait;
    use RegisterTrait;

    public function __invoke(
        string $projectId,
        ProjectRepository $projectRepository,
        RequestCreditPurchaseService $requestCreditPurchase,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $project = $this->getProjectFromId($projectId, $projectRepository);

        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        $creditPurchaseRequest = new RequestCreditPurchaseRequest(
            accountId: $account->id(),
            projectId: $project->id(),
            tonnes: 0.0,
        );

        $form = $this->createForm(RequestCreditPurchaseType::class, $creditPurchaseRequest);

        try {
            $needsRedirect = $this->register($request, $form, function ($form) use ($requestCreditPurchase, $loggedUser) {
                $requestCreditPurchase($form->getData(), $loggedUser());

                $this->addFlash('success', new TranslatableMessage('Solicitud de créditos enviada correctamente'));

                return true;
            });

            if ($needsRedirect) {
                return $this->redirectToRoute('projects_view', ['projectId' => $project->id()]);
            }
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/credits/request.html.twig', [
            'project' => $project,
            'form' => $form->createView(),
        ]);
    }
}
