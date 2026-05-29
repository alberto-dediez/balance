<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\UI\Web\Admin\User;

use App\Security\Application\Action;
use App\Security\Application\RecordedEvent\List\ListRecordedEventsService;
use App\Security\Domain\Model\User\PasswordChanged;
use App\Security\Domain\Model\User\PasswordRecovered;
use App\Security\Domain\Model\User\PasswordRecoveryInitialized;
use App\Security\Domain\Model\User\RoleGranted;
use App\Security\Domain\Model\User\RoleUngranted;
use App\Security\Domain\Model\User\UserRegistered;
use App\Security\Domain\Model\User\UserRemoved;
use App\Security\Domain\Model\User\UserRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Security\Infrastructure\UI\Web\User\GetUserFromIdTrait;
use App\Shared\Infrastructure\UI\Web\CRUD\AggregateHistoryTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/users/{userId}/history', name: 'admin_user_history')]
class HistoryController extends AbstractController
{
    use AggregateHistoryTrait, GetUserFromIdTrait;

    protected function allowedEventNames(): array
    {
        return [
            UserRegistered::eventName(),
            UserRemoved::eventName(),
            RoleGranted::eventName(),
            RoleUngranted::eventName(),
            PasswordChanged::eventName(),
            PasswordRecovered::eventName(),
            PasswordRecoveryInitialized::eventName(),
        ];
    }

    protected function allowedContext(): string
    {
        return 'security';
    }

    public function __invoke(
        string $userId,
        ListRecordedEventsService $listEvents,
        UserRepository $userRepository,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
        Request $request
    ): Response {
        $user = $this->getUserFromId($userId, $userRepository);

        $this->denyAccessUnlessGranted(Action::History->value, $user);

        $pageTitle = $translator->trans('Usuarios')
            . ' > '
            . (string) $user
            . ' > '
            . $translator->trans('Histórico')
        ;

        return $this->renderAggregateHistory(
            aggregateRootId: $userId,
            pageTitle: $pageTitle,
            backToList: $this->generateUrl('admin_users'),
            listEvents: $listEvents,
            performBy: $loggedUser(),
            request: $request,
        );
    }
}
