<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Assistant;

use App\Library\Application\Media\Download\DownloadMediaRequest;
use App\Library\Application\Media\Download\DownloadMediaService;
use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaNotFoundException;
use App\Library\Domain\Model\Media\NotStoredMediaException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolDocument;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

class ReadMediaTool implements AssistantTool
{
    public function __construct(
        private readonly DownloadMediaService $downloadMedia,
        private readonly MediaAuthorizer $authorizer,
        private readonly CurrentAccountIdProvider $currentAccount,
    ) {}

    public function name(): string
    {
        return 'read_media';
    }

    public function label(): string
    {
        return 'Leer fichero';
    }

    public function description(): string
    {
        return 'Abre un fichero de la biblioteca o un adjunto de una tarea, ticket o propuesta (texto, Markdown, CSV, HTML, JSON, PDF o imagen). Necesita su mediaId, que devuelven list_media, view_task, view_ticket y view_proposal.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::id('mediaId', 'Fichero', 'Id del fichero.')->required(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Media(MediaId::generate(), $this->currentAccount->currentAccountId()), Action::View, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $mediaId = new MediaId((string) $arguments->string('mediaId'));

        try {
            $response = ($this->downloadMedia)(new DownloadMediaRequest($mediaId), $user);
        } catch (MediaNotFoundException|NotStoredMediaException) {
            throw InvalidToolArgumentsException::because('El fichero no existe o no está disponible');
        }

        try {
            if ($response->size > $context->documentMaxBytes) {
                throw InvalidToolArgumentsException::because(sprintf('El fichero «%s» supera el tamaño máximo', $response->fileName));
            }

            $contents = stream_get_contents($response->stream, $context->documentMaxBytes + 1);

            if (false === $contents) {
                throw InvalidToolArgumentsException::because('El fichero no existe o no está disponible');
            }

            $document = ToolDocument::from($response->fileName, $response->mimeType, $contents, $context->documentMaxBytes);
        } finally {
            fclose($response->stream);
        }

        return new ToolResult(
            summary: sprintf('Fichero «%s»', $response->fileName),
            data: [
                'mediaId' => (string) $mediaId,
                'name' => $response->fileName,
                'mimeType' => $response->mimeType,
                'size' => $response->size,
                ...$document->data(),
            ],
            references: ['mediaId' => (string) $mediaId],
            link: new ToolLink('Ver fichero', 'library_media_show', ['mediaId']),
            attachments: $document->attachments(),
        );
    }
}
