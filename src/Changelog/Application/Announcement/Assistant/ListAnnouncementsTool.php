<?php

declare(strict_types=1);

namespace App\Changelog\Application\Announcement\Assistant;

use App\Changelog\Application\Announcement\AnnouncementAuthorizer;
use App\Changelog\Application\Announcement\List\ListAnnouncementsRequest;
use App\Changelog\Application\Announcement\List\ListAnnouncementsService;
use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\AnnouncementStatus;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\AssistantTool;
use App\Shared\Application\Assistant\ToolArguments;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameter;
use App\Shared\Application\Assistant\ToolResult;

class ListAnnouncementsTool implements AssistantTool
{
    public function __construct(
        private readonly ListAnnouncementsService $listAnnouncements,
        private readonly AnnouncementAuthorizer $authorizer,
    ) {}

    public function name(): string
    {
        return 'list_announcements';
    }

    public function label(): string
    {
        return 'Listar anuncios';
    }

    public function description(): string
    {
        return 'Lista los anuncios de la plataforma filtrando por texto, categoría y estado (draft, scheduled, published). Devuelve su id para usar con view_announcement.';
    }

    public function mode(): ToolMode
    {
        return ToolMode::Read;
    }

    public function parameters(): array
    {
        return [
            ToolParameter::string('text', 'Texto', 'Texto a buscar en el anuncio.'),
            ToolParameter::enum('category', 'Categoría', 'Categoría del anuncio.', array_column(ChangelogCategory::cases(), 'value')),
            ToolParameter::enum('status', 'Estado', 'Estado del anuncio.', array_column(AnnouncementStatus::cases(), 'value')),
            ToolParameter::limit(),
        ];
    }

    public function isAvailableTo(User $user): bool
    {
        return $this->authorizer->canDoThat(new Announcement(AnnouncementId::generate()), Action::List, $user);
    }

    public function execute(ToolArguments $arguments, User $user, ToolContext $context): ToolResult
    {
        $category = $arguments->string('category');
        $status = $arguments->string('status');

        $request = new ListAnnouncementsRequest(
            text: $arguments->string('text'),
            category: null === $category ? null : ChangelogCategory::from($category),
            status: null === $status ? null : AnnouncementStatus::from($status),
            limit: $arguments->limit(),
        );

        $response = ($this->listAnnouncements)($request, $user);

        return new ToolResult(
            summary: sprintf('%d anuncios encontrados', $response->totalResults),
            data: [
                'announcements' => array_map(
                    static fn(Announcement $announcement): array => [
                        'id' => (string) $announcement->id(),
                        'title' => $announcement->title(),
                        'summary' => $announcement->summary(),
                        'category' => $announcement->category()->value,
                        'status' => $announcement->status()->value,
                        'date' => $context->date($announcement->date()),
                        'publishedAt' => $context->dateTime($announcement->publishedAt()),
                        'scheduledAt' => $context->dateTime($announcement->scheduledAt()),
                        'hasManual' => $announcement->hasManual(),
                        'hasVideo' => $announcement->hasVideo(),
                    ],
                    $response->announcements,
                ),
                'total' => $response->totalResults,
            ],
        );
    }
}
