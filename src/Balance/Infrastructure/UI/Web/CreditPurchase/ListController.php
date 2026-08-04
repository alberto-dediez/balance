<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\CreditPurchase;

use App\Balance\Application\AccountProject\List\ListAccountProjectsRequest;
use App\Balance\Application\AccountProject\List\ListAccountProjectsService;
use App\Balance\Application\CreditPurchase\List\ListCreditPurchasesRequest;
use App\Balance\Application\CreditPurchase\List\ListCreditPurchasesService;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Infrastructure\Symfony\Form\ListCreditPurchasesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/credits/purchases', name: 'credit_purchases')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListCreditPurchasesService $listCreditPurchases,
        ListAccountProjectsService $listAccountProjects,
        ProjectRepository $projectRepository,
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        $account = $loggedAccount();

        if (null == $account) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            $accountProjectsResponse = $listAccountProjects(
                new ListAccountProjectsRequest(accountId: $account->id(), limit: 0),
                $loggedUser(),
            );

            $projectChoices = [];

            foreach ($accountProjectsResponse->accountProjects as $accountProject) {
                $projectId = $accountProject->projectId();
                $project = null !== $projectId ? $projectRepository->find($projectId) : null;

                if (null !== $project) {
                    $projectChoices[$project->name()] = $project->id();
                }
            }

            [$filter, $form] = $this->filter(
                $request,
                fn(): ListCreditPurchasesRequest => new ListCreditPurchasesRequest(accountId: $account->id()),
                ListCreditPurchasesType::class,
                ['projects' => $projectChoices],
            );

            assert($filter instanceof ListCreditPurchasesRequest);

            $filter->accountId = $account->id();

            $response = $listCreditPurchases($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/credits/purchases.html.twig', [
            'creditPurchases' => $response->creditPurchases,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
