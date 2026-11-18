<?php

declare(strict_types=1);

namespace App\Assistant\Infrastructure\UI\Web\User\Assistant;

use App\Assistant\Application\AssistantSettings;
use App\Assistant\Application\Conversation\SendMessage\MessageAttachmentUpload;
use App\Assistant\Application\Conversation\SendMessage\SendMessageRequest;
use App\Assistant\Application\Conversation\SendMessage\SendMessageService;
use App\Assistant\Application\Engine\DailyLimitReachedException;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\InvalidMessageException;
use App\Assistant\Infrastructure\Symfony\Form\SendAssistantMessageType;
use App\Assistant\Infrastructure\UI\Web\AssistantAccess;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use App\Shared\Application\CurrentTimezone\CurrentTimezoneProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/assistant/messages', name: 'assistant_message_send', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
class SendController extends AbstractController
{
    public function __invoke(
        Request $request,
        AssistantAccess $access,
        AssistantSettings $settings,
        SendMessageService $sendMessage,
        CurrentTimezoneProvider $timezoneProvider,
        LoggedUser $loggedUser,
        TranslatorInterface $translator,
    ): Response {
        if (!$access->isGranted()) {
            throw $this->createAccessDeniedException();
        }

        $message = new SendMessageRequest();
        $form = $this->createForm(SendAssistantMessageType::class, $message, ['document_max_bytes' => $settings->documentMaxBytes]);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                return $this->unprocessable($error->getMessage());
            }

            return $this->unprocessable($translator->trans('El mensaje no es válido'));
        }

        $message->timezone = $timezoneProvider->currentTimezone()->getName();

        /** @var list<UploadedFile> $files */
        $files = $form->get('attachments')->getData() ?? [];
        $message->attachments = array_map(
            static fn(UploadedFile $file): MessageAttachmentUpload => new MessageAttachmentUpload(
                $file->getPathname(),
                $file->getClientOriginalName(),
                (string) $file->getMimeType(),
                (int) $file->getSize(),
                strtolower($file->getClientOriginalExtension()),
            ),
            $files,
        );

        try {
            $response = $sendMessage($message, $loggedUser());
        } catch (ConversationBusyException|DailyLimitReachedException|InvalidMessageException $exception) {
            return $this->unprocessable($translator->trans($exception->getMessage()));
        } catch (\InvalidArgumentException|UnauthorizedActionException $exception) {
            throw $this->createNotFoundException($exception->getMessage());
        }

        $json = new JsonResponse(['conversationId' => (string) $response->conversationId]);
        $json->headers->set('Cache-Control', 'no-store');

        return $json;
    }

    private function unprocessable(string $message): Response
    {
        return new Response($message, Response::HTTP_UNPROCESSABLE_ENTITY, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
