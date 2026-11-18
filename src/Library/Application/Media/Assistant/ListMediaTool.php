<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Assistant;

use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Application\Media\List\ListMediaService;
use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Media\Media;
use App\Library\Domain\Model\Media\MediaId;
use App\Library\Domain\Model\Media\MediaType;
use App\Security\Application\Action;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolDocument;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;

class ListMediaTool implements AssistantTool
{
    public function __construct(
        private readonly ListMediaService $listMedia,
        private readonly MediaAuthorizer $authorizer,
        private readonly CurrentAccountIdProvider $currentAccount,
    ) {}

    public function name(): string
    {
        return 'list_media';
    }

    public function label(): string
    {
        return 'Listar ficheros de la biblioteca';
    }

    public function description(): string
    {
        return 'Lista los ficheros de la biblioteca de medios por nombre y tipo, del más reciente al más antiguo. Después se abren con read_media.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('name', 'Nombre', 'Parte del nombre del fichero.'),
            ToolParameter::enum('type', 'Tipo', 'Tipo de fichero.', array_column(MediaType::cases(), 'value')),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Media(MediaId::generate(), $this->currentAccount->currentAccountId()), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $type = $arguments->string('type');
        $accountId = $this->currentAccount->currentAccountId();

        $response = ($this->listMedia)(
            new ListMediaRequest(
                name: $arguments->string('name'),
                type: null === $type ? null : MediaType::from($type),
                accountId: $accountId,
                withoutAccount: !$accountId instanceof AccountId,
                limit: $arguments->limit(),
            ),
            $user,
        );

        return new ToolResult(
            summary: sprintf('%d ficheros encontrados', $response->totalResults),
            data: [
                'media' => array_map(
                    static fn(Media $media): array => [
                        'mediaId' => (string) $media->id(),
                        'href' => ToolLink::href('library_media_show', ['mediaId' => (string) $media->id()]),
                        'name' => $media->name(),
                        'fileName' => $media->fileName(),
                        'mimeType' => $media->mimeType(),
                        'type' => $media->type()->value,
                        'size' => $media->size(),
                        'registeredAt' => $context->dateTime($media->registeredAt()),
                        'readable' => ToolDocument::isReadable($media->mimeType()),
                    ],
                    $response->media,
                ),
                'total' => $response->totalResults,
            ],
        );
    }
}
