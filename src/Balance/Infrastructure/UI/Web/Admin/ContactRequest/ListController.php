<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\UI\Web\Admin\ContactRequest;

use App\Balance\Application\ContactRequest\List\ListContactRequestsRequest;
use App\Balance\Application\ContactRequest\List\ListContactRequestsService;
use App\Balance\Infrastructure\Symfony\Form\ListContactRequestsType;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\Account\AccountFilter;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/contact-requests', name: 'admin_contact_requests')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(
        ListContactRequestsService $listContactRequests,
        AccountRepository $accountRepository,
        LoggedUser $loggedUser,
        Request $request,
    ): Response {
        try {
            $accountChoices = [];

            foreach ($accountRepository->findByFilter(new AccountFilter(limit: 0)) as $account) {
                $accountChoices[$account->name()] = $account->id();
            }

            [$filter, $form] = $this->filter(
                $request,
                fn(): ListContactRequestsRequest => new ListContactRequestsRequest(resolved: false),
                ListContactRequestsType::class,
                ['accounts' => $accountChoices],
            );

            $response = $listContactRequests($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@balance/admin/contact-requests/list.html.twig', [
            'contactRequests' => $response->contactRequests,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
