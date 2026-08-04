<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\CreditPurchase;

use App\Balance\Application\CreditPurchase\List\ListCreditPurchasesRequest;
use App\Balance\Application\CreditPurchase\List\ListCreditPurchasesService;
use App\Balance\Domain\Model\Project\ProjectFilter;
use App\Balance\Domain\Model\Project\ProjectRepository;
use App\Balance\Domain\Model\Project\ProjectType;
use App\Balance\Infrastructure\Symfony\Form\ListCreditPurchasesType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/credits', name: 'admin_credit_purchases')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListCreditPurchasesService $listCreditPurchases,
        ProjectRepository $projectRepository,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        try {
            $projectChoices = [];

            foreach ($projectRepository->findByFilter(new ProjectFilter(projectType: ProjectType::EmissionCompensation, limit: 0)) as $project) {
                $projectChoices[$project->name()] = $project->id();
            }

            [$filter, $form] = $this->filter(
                $request,
                fn(): ListCreditPurchasesRequest => new ListCreditPurchasesRequest(),
                ListCreditPurchasesType::class,
                ['projects' => $projectChoices],
            );

            $response = $listCreditPurchases($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/credits/list.html.twig', [
            'creditPurchases' => $response->creditPurchases,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
