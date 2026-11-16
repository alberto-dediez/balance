<?php

declare(strict_types=1);

namespace App\Changelog\Application\ChangelogEntry\Assistant;

use App\Changelog\Application\ChangelogEntry\ChangelogEntryAuthorizer;
use App\Changelog\Application\ChangelogEntry\ViewTechnicalManual\ViewTechnicalManualRequest;
use App\Changelog\Application\ChangelogEntry\ViewTechnicalManual\ViewTechnicalManualService;
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
use App\Shared\Application\Assistant\ToolLink;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ReadChangelogTechnicalManualTool implements AssistantTool
{
    public const string NO_MANUAL = 'La novedad no tiene manual técnico.';

    public function __construct(
        private readonly ViewTechnicalManualService $viewTechnicalManual,
        private readonly ChangelogEntryAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'read_changelog_technical_manual';
    }

    public function label(): string
    {
        return 'Leer manual técnico de novedad';
    }

    public function description(): string
    {
        return 'Devuelve en Markdown el manual técnico de una novedad publicada que lo tenga (hasTechnicalManual).';
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
        return $this->authorizer->canDoThat(ChangelogEntry::placeholder(), Action::History, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        try {
            $response = ($this->viewTechnicalManual)(new ViewTechnicalManualRequest($this->slug($arguments)), $user);
        } catch (ChangelogEntryNotFoundException) {
            throw InvalidToolArgumentsException::because(ViewChangelogEntryTool::NOT_FOUND);
        } catch (ManualNotFoundException) {
            throw InvalidToolArgumentsException::because(self::NO_MANUAL);
        } catch (InvalidChangelogEntryException) {
            throw InvalidToolArgumentsException::because(ViewChangelogEntryTool::UNREADABLE);
        }
        [$markdown, $truncated] = ToolResult::truncate($response->markdown);

        return new ToolResult(
            summary: sprintf('Manual técnico de «%s»', $response->entry->title),
            data: [
                'slug' => (string) $response->entry->slug,
                'markdown' => $markdown,
                'truncated' => $truncated,
            ],
            references: ['slug' => (string) $response->entry->slug],
            link: new ToolLink('Ver manual técnico', 'admin_changelog_tech_manual', ['slug']),
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
