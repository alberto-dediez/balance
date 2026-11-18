<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\View\ViewChangelogEntryRequest;
use App\Changelog\Application\ChangelogEntry\View\ViewChangelogEntryService;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntry;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntryNotFoundException;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogEntrySlug;
use App\Changelog\Domain\Model\ChangelogEntry\InvalidChangelogEntryException;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ViewChangelogEntryTool implements AssistantTool
{
    public const string NOT_FOUND = 'La novedad no existe; búscala con list_changelog_entries.';
    public const string UNREADABLE = 'La novedad no se puede leer.';

    public function __construct(
        private readonly ViewChangelogEntryService $viewChangelogEntry,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'view_changelog_entry';
    }

    public function label(): string
    {
        return 'Ver novedad';
    }

    public function description(): string
    {
        return 'Devuelve una novedad publicada con su cuerpo en Markdown e indica si tiene manual de usuario o manual técnico.';
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
        try {
            $entry = ($this->viewChangelogEntry)(new ViewChangelogEntryRequest($this->slug($arguments)), $user)->entry;
        } catch (ChangelogEntryNotFoundException) {
            throw InvalidToolArgumentsException::because(self::NOT_FOUND);
        } catch (InvalidChangelogEntryException) {
            throw InvalidToolArgumentsException::because(self::UNREADABLE);
        }
        [$body, $truncated] = ToolResult::truncate($entry->body);

        return new ToolResult(
            summary: sprintf('Novedad «%s» del %s', $entry->title, $context->date($entry->date)),
            data: [
                'slug' => (string) $entry->slug,
                'title' => $entry->title,
                'date' => $context->date($entry->date),
                'category' => $entry->category->value,
                'version' => $entry->version,
                'summary' => $entry->summary,
                'body' => $body,
                'truncated' => $truncated,
                'hasManual' => $entry->hasManual(),
                'hasTechnicalManual' => $entry->hasTechnicalManual(),
            ],
            references: ['slug' => (string) $entry->slug],
            link: new ToolLink('Ver novedad', 'admin_changelog_entry', ['slug']),
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
