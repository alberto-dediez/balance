<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\Admin\Usage;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Domain\Model\Conversation\Conversation;
use App\Assistant\Domain\Model\Conversation\ConversationId;
use App\Security\Application\Action;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\Period\DailyPeriod;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/assistant/usage', name: 'admin_assistant_usage', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
class ViewController extends AbstractController
{
    public function __invoke(Request $request, AssistantSettings $settings, ConversationAuthorizer $authorizer, LoggedUser $loggedUser): Response
    {
        if (!$settings->enabled || !$authorizer->canDoThat(new Conversation(ConversationId::generate()), Action::Admin, $loggedUser())) {
            throw $this->createAccessDeniedException();
        }

        $date = $this->date($request->query->getString('date'));

        return $this->render('@assistant/admin/usage/show.html.twig', [
            'period' => DailyPeriod::of($request->query->getString('period', DailyPeriod::WEEK), $date),
            'date' => $date,
            'now' => new \DateTimeImmutable(),
        ]);
    }

    private function date(string $date): \DateTimeImmutable
    {
        $parsed = '' === $date ? false : \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return false === $parsed ? new \DateTimeImmutable('today') : $parsed;
    }
}
