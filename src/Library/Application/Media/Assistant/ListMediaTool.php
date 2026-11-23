<?php

declare(strict_types=1);

namespace App\Library\Application\Media\Assistant;

use App\Library\Application\Folder\CurrentAccountFolders;
use App\Library\Application\Media\List\ListMediaRequest;
use App\Library\Application\Media\List\ListMediaService;
use App\Library\Application\Media\MediaAuthorizer;
use App\Library\Domain\Model\Folder\Folder;
use App\Library\Domain\Model\Folder\FolderId;
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
        private readonly CurrentAccountFolders $folders,
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
        return 'Lista los ficheros de la biblioteca de medios por nombre y tipo, del más reciente al más antiguo. El nombre también encuentra los ficheros por el nombre de su carpeta y folder acota a las carpetas cuyo nombre lo contiene. Después se abren con read_media.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('name', 'Nombre', 'Parte del nombre del fichero.'),
            ToolParameter::string('folder', 'Carpeta', 'Parte del nombre de la carpeta en la que buscar. Sin él se listan los ficheros de todas las carpetas.'),
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
        $folder = $arguments->string('folder');

        $folderIds = [];

        if (null !== $folder && '' !== $folder) {
            $folderIds = array_map(static fn(Folder $candidate): FolderId => $candidate->id(), $this->folders->matchingName($folder));

            if ([] === $folderIds) {
                return new ToolResult(
                    summary: 'No hay ninguna carpeta con ese nombre',
                    data: [
                        'media' => [],
                        'total' => 0,
                    ],
                );
            }
        }

        $request = new ListMediaRequest(
            name: $arguments->string('name'),
            type: null === $type ? null : MediaType::from($type),
            accountId: $accountId,
            withoutAccount: !$accountId instanceof AccountId,
            limit: $arguments->limit(),
            folderIds: $folderIds,
        );

        $request->searchInFolderNames = true;

        $response = ($this->listMedia)($request, $user);

        $folderNames = [];

        foreach ($this->folders->all() as $candidate) {
            $folderNames[(string) $candidate->id()] = $candidate->name();
        }

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
                        'folder' => $media->folderId() instanceof FolderId ? ($folderNames[(string) $media->folderId()] ?? null) : null,
                    ],
                    $response->media,
                ),
                'total' => $response->totalResults,
            ],
        );
    }
}
