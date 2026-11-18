<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\SearchDocuments\ChangelogDocumentMatch;
use App\Changelog\Application\ChangelogEntry\SearchDocuments\SearchChangelogDocumentsRequest;
use App\Changelog\Application\ChangelogEntry\SearchDocuments\SearchChangelogDocumentsService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class SearchChangelogDocumentsTool implements AssistantTool
{
    public function __construct(
        private readonly SearchChangelogDocumentsService $searchDocuments,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'search_changelog_documents';
    }

    public function label(): string
    {
        return 'Buscar en documentos de novedades';
    }

    public function description(): string
    {
        return 'Busca un texto dentro del cuerpo de las novedades publicadas y, si el usuario es super administrador, también en sus manuales técnicos. Devuelve un fragmento por coincidencia y el origen (entry o technical_manual). No busca en los manuales de usuario en PDF. Usa el slug devuelto con view_changelog_entry o read_changelog_technical_manual.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('text', 'Texto', 'Texto a buscar (entre 3 y 255 caracteres).')->required(),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(ChangelogEntry::placeholder(), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $response = ($this->searchDocuments)(new SearchChangelogDocumentsRequest((string) $arguments->string('text'), $arguments->limit()), $user);

        return new ToolResult(
            summary: sprintf('%d coincidencias en documentos de novedades', $response->total),
            data: [
                'matches' => array_map(
                    static fn(ChangelogDocumentMatch $match): array => [
                        'slug' => (string) $match->entry->slug,
                        'href' => ToolLink::href('admin_changelog_entry', ['slug' => (string) $match->entry->slug]),
                        'title' => $match->entry->title,
                        'date' => $context->date($match->entry->date),
                        'source' => $match->source,
                        'excerpt' => $match->excerpt,
                    ],
                    $response->matches,
                ),
                'total' => $response->total,
            ],
        );
    }
}
