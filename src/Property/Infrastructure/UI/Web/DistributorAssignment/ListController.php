<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\UI\Web\DistributorAssignment;

use App\Property\Application\DistributorAssignment\List\ListDistributorAssignmentsRequest;
use App\Property\Application\DistributorAssignment\List\ListDistributorAssignmentsService;
use App\Property\Infrastructure\Symfony\Form\ListOwnerDistributorAssignmentsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/distributors', name: 'distributors')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        LoggedUser $loggedUser,
        LoggedAccount $loggedAccount,
        ListDistributorAssignmentsService $listDistributorAssignments,
        TranslatorInterface $translator,
        Request $request,
    ): Response {
        if (null === $loggedAccount()) {
            throw $this->createAccessDeniedException($translator->trans('Debes pertenecer a una cuenta para poder acceder'));
        }

        try {
            [$filter, $form] = $this->filter($request, fn(): ListDistributorAssignmentsRequest => new ListDistributorAssignmentsRequest(), ListOwnerDistributorAssignmentsType::class);

            $response = $listDistributorAssignments($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@property/distributors/list.html.twig', [
            'assignments' => $response->assignments,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
