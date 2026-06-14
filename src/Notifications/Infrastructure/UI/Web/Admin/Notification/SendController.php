<?php

declare(strict_types=1);

namespace App\Notifications\Infrastructure\UI\Web\Admin\Notification;

use App\Notifications\Application\Notification\Send\SendNotificationRequest;
use App\Notifications\Domain\Model\Notification\Notification;
use App\Notifications\Domain\Model\Notification\NotificationId;
use App\Notifications\Infrastructure\Symfony\Form\SendNotificationForm;
use App\Notifications\Infrastructure\Symfony\Form\SendNotificationType;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\Account\Account;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CommandBus\CommandBusDispatcher;
use App\Shared\Application\CommandBus\DeferredCommand;
use App\Shared\Infrastructure\UI\Web\CRUD\RegisterTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/admin/notifications/send', name: 'admin_notifications_send')]
#[IsGranted('ROLE_ADMIN')]
class SendController extends AbstractController
{
    use RegisterTrait;

    public function __invoke(LoggedUser $loggedUser, Request $request): Response
    {
        $this->denyAccessUnlessGranted(SecurityAction::Register->value, new Notification(NotificationId::generate()));

        $form = $this->createForm(SendNotificationType::class, new SendNotificationForm());

        $needsRedirect = $this->register($request, $form, function ($form) use ($loggedUser): bool {
            /** @var SendNotificationForm $data */
            $data = $form->getData();

            $command = new SendNotificationRequest(
                audienceType: $data->audienceType,
                targetUserId: null !== $data->targetUser ? (string) $data->targetUser : null,
                targetAccountId: $data->targetAccount instanceof Account ? (string) $data->targetAccount->id() : null,
                targetRole: $data->targetRole,
                channels: $data->channels,
                subject: $data->subject,
                body: $data->body,
            );

            CommandBusDispatcher::instance()->dispatch(new DeferredCommand($command), $loggedUser());

            $this->addFlash('success', new TranslatableMessage('Notificación encolada para envío'));

            return true;
        });

        if ($needsRedirect) {
            return $this->redirectToRoute('admin_notifications');
        }

        return $this->render('@notifications/admin/send.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
