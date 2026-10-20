<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\EmailLog;

use App\Security\Application\EmailLog\List\ListEmailLogsRequest;
use App\Security\Application\EmailLog\List\ListEmailLogsService;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Form\ListEmailLogsType;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Infrastructure\UI\Web\CRUD\FilterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/emails', name: 'admin_emails')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class ListController extends AbstractController
{
    use FilterTrait;

    public function __invoke(ListEmailLogsService $listEmailLogs, LoggedUser $loggedUser, Request $request): Response
    {
        [$filter, $form] = $this->filter($request, fn(): ListEmailLogsRequest => new ListEmailLogsRequest(), ListEmailLogsType::class);

        try {
            $response = $listEmailLogs($filter, $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        }

        return $this->render('@security/admin/emails/list.html.twig', [
            'emailLogs' => $response->emailLogs,
            'totalResults' => $response->totalResults,
            'form' => $form->createView(),
        ]);
    }
}
