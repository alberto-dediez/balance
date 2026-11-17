<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Assistant;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\View\ViewAnnouncementRequest;
use App\Changelog\Application\Announcement\View\ViewAnnouncementService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementNotFoundException;
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

class ViewAnnouncementTool implements AssistantTool
{
    public const string NOT_FOUND = 'El anuncio no existe; búscalo con list_announcements.';

    public function __construct(
        private readonly ViewAnnouncementService $viewAnnouncement,
        private readonly AnnouncementAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'view_announcement';
    }

    public function label(): string
    {
        return 'Ver anuncio';
    }

    public function description(): string
    {
        return 'Devuelve un anuncio con su cuerpo en Markdown. Si tiene manual (hasManual), manualMediaId se puede leer con read_media. Necesita el id del anuncio (list_announcements).';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::id('announcementId', 'Anuncio', 'Id del anuncio.')->required(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Announcement(AnnouncementId::generate()), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        try {
            $announcement = ($this->viewAnnouncement)(new ViewAnnouncementRequest(new AnnouncementId((string) $arguments->string('announcementId'))), $user)->announcement;
        } catch (AnnouncementNotFoundException) {
            throw InvalidToolArgumentsException::because(self::NOT_FOUND);
        }

        [$body, $truncated] = ToolResult::truncate($announcement->body());

        return new ToolResult(
            summary: sprintf('Anuncio «%s»', $announcement->title()),
            data: [
                'id' => (string) $announcement->id(),
                'title' => $announcement->title(),
                'summary' => $announcement->summary(),
                'body' => $body,
                'truncated' => $truncated,
                'category' => $announcement->category()->value,
                'status' => $announcement->status()->value,
                'version' => $announcement->version(),
                'date' => $context->date($announcement->date()),
                'publishedAt' => $context->dateTime($announcement->publishedAt()),
                'scheduledAt' => $context->dateTime($announcement->scheduledAt()),
                'hasManual' => $announcement->hasManual(),
                'manualMediaId' => null === $announcement->manualId() ? null : (string) $announcement->manualId(),
                'hasVideo' => $announcement->hasVideo(),
            ],
            references: ['announcementId' => (string) $announcement->id()],
            link: new ToolLink('Ver anuncio', 'admin_announcements_edit', ['announcementId']),
        );
    }
}
