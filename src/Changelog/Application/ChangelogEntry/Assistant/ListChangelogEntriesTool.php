<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\List\ListChangelogEntriesRequest;
use App\Changelog\Application\ChangelogEntry\List\ListChangelogEntriesService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ListChangelogEntriesTool implements AssistantTool
{
    public function __construct(
        private readonly ListChangelogEntriesService $listChangelogEntries,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'list_changelog_entries';
    }

    public function label(): string
    {
        return 'Listar novedades';
    }

    public function description(): string
    {
        return 'Busca en las novedades publicadas de la plataforma por texto del título o el resumen y por categoría. Úsala para saber qué se ha publicado y obtener el slug de una novedad; para buscar un texto dentro del cuerpo o del manual técnico usa search_changelog_documents.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('text', 'Texto', 'Texto a buscar en el título o el resumen de las novedades.'),
            ToolParameter::enum('category', 'Categoría', 'Categoría de la novedad.', array_column(ChangelogCategory::cases(), 'value')),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(ChangelogEntry::placeholder(), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $category = $arguments->string('category');

        $request = new ListChangelogEntriesRequest(
            text: $arguments->string('text'),
            category: null === $category ? null : ChangelogCategory::from($category),
        );
        $request->limit = $arguments->limit();

        $response = ($this->listChangelogEntries)($request, $user);

        return new ToolResult(
            summary: sprintf('%d novedades encontradas', $response->totalResults),
            data: [
                'entries' => array_map(
                    static fn(ChangelogEntry $entry): array => [
                        'slug' => (string) $entry->slug,
                        'title' => $entry->title,
                        'date' => $context->date($entry->date),
                        'category' => $entry->category->value,
                        'summary' => $entry->summary,
                        'hasManual' => $entry->hasManual(),
                        'hasTechnicalManual' => $entry->hasTechnicalManual(),
                    ],
                    $response->entries,
                ),
                'total' => $response->totalResults,
            ],
        );
    }
}
