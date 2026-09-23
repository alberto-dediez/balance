<?php

declare(strict_types=1);

namespace App\Changelog\Domain\Model\Announcement;

use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\Translation;

class AnnouncementTranslation implements Translation
{
    public function __construct(
        private string $title,
        private string $summary,
        private string $body,
        private ?YouTubeVideo $youtubeVideo,
        private ?string $manualFileName,
        private ?string $manualPath,
        private Locale $locale,
        private Announcement $translatable,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function youtubeVideo(): ?YouTubeVideo
    {
        return $this->youtubeVideo;
    }

    public function manualFileName(): ?string
    {
        return $this->manualFileName;
    }

    public function manualPath(): ?string
    {
        return $this->manualPath;
    }

    public function locale(): Locale
    {
        return $this->locale;
    }

    public function translatable(): Announcement
    {
        return $this->translatable;
    }

    public function update(Translation $translation): void
    {
        assert($translation instanceof AnnouncementTranslation);

        $this->title = $translation->title();
        $this->summary = $translation->summary();
        $this->body = $translation->body();
        $this->youtubeVideo = $translation->youtubeVideo();
        $this->manualFileName = $translation->manualFileName();
        $this->manualPath = $translation->manualPath();
    }
}
