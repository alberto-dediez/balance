<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\EmailLog;

use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\EmailLog\EmailLog;
use App\Security\Domain\Model\EmailLog\EmailLogId;
use App\Security\Domain\Model\EmailLog\EmailLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/emails/{emailLogId}', name: 'admin_emails_show', methods: ['GET'])]
#[IsGranted('ROLE_SUPER_ADMIN')]
class ShowController extends AbstractController
{
    public function __invoke(string $emailLogId, EmailLogRepository $repository): Response
    {
        $emailLog = $repository->find(new EmailLogId($emailLogId));

        if (!$emailLog instanceof EmailLog) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(SecurityAction::View->value, $emailLog);

        return $this->render('@security/admin/emails/show.html.twig', [
            'emailLog' => $emailLog,
        ]);
    }
}
