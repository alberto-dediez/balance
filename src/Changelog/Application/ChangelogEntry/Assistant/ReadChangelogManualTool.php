<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\DownloadManual\DownloadManualRequest;
use App\Changelog\Application\ChangelogEntry\DownloadManual\DownloadManualService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\InvalidChangelogEntryException;
use App\Changelog\Domain\Model\ChangelogEntry\ManualNotFoundException;
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

class ReadChangelogManualTool implements AssistantTool
{
    public const string NO_MANUAL = 'La novedad no tiene manual de usuario.';

    public function __construct(
        private readonly DownloadManualService $downloadManual,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'read_changelog_manual';
    }

    public function label(): string
    {
        return 'Leer manual de novedad';
    }

    public function description(): string
    {
        return 'Abre el manual de usuario en PDF de una novedad publicada que lo tenga (hasManual) para que puedas leer su texto y sus capturas. Úsala solo cuando el usuario pida el manual de una novedad concreta; para buscar texto usa search_changelog_documents.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('slug', 'Novedad', 'Slug de la novedad, tal como lo devuelve list_changelog_entries.')->required(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(ChangelogEntry::placeholder(), Action::View, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $slug = $this->slug($arguments);

        try {
            $response = ($this->downloadManual)(new DownloadManualRequest($slug), $user);
        } catch (ChangelogEntryNotFoundException) {
            throw InvalidToolArgumentsException::because(ViewChangelogEntryTool::NOT_FOUND);
        } catch (ManualNotFoundException) {
            throw InvalidToolArgumentsException::because(self::NO_MANUAL);
        }

        if ($response->size > $context->documentMaxBytes) {
            fclose($response->stream);

            throw InvalidToolArgumentsException::because(sprintf('El fichero «%s» supera el tamaño máximo', $response->fileName));
        }

        $contents = (string) stream_get_contents($response->stream);
        fclose($response->stream);

        $document = ToolDocument::from($response->fileName, $response->mimeType, $contents, $context->documentMaxBytes);

        return new ToolResult(
            summary: sprintf('Manual de usuario «%s»', $response->fileName),
            data: [
                'slug' => (string) $slug,
                'name' => $response->fileName,
                'mimeType' => $response->mimeType,
                'size' => $response->size,
                ...$document->data(),
            ],
            references: ['slug' => (string) $slug],
            link: new ToolLink('Ver novedad', 'admin_changelog_entry', ['slug']),
            attachments: $document->attachments(),
        );
    }

    private function slug(ToolArguments $arguments): ChangelogEntrySlug
    {
        try {
            return new ChangelogEntrySlug((string) $arguments->string('slug'));
        } catch (InvalidChangelogEntryException) {
            throw InvalidToolArgumentsException::because('«slug»: no es un slug de novedad válido.');
        }
    }
}
