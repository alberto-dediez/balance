<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\Announcement;

use App\Changelog\Domain\Model\Announcement\Announcement;
use App\Changelog\Domain\Model\Announcement\AnnouncementId;
use App\Changelog\Domain\Model\Announcement\YouTubeVideo;
use App\Changelog\Domain\Model\ChangelogEntry\ChangelogCategory;
use App\Library\Domain\Model\Media\MediaId;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class AnnouncementMother
{
    public static function registeredAnnouncement(): Announcement
    {
        return self::fromTitle('title');
    }

    public static function fromTitle(string $title): Announcement
    {
        return self::from(
            title: $title,
            id: (string) AnnouncementId::generate(),
        );
    }

    public static function fromTitleAndId(string $title, string $id): Announcement
    {
        return self::from(
            title: $title,
            id: $id,
        );
    }

    public static function from(
        string $title,
        string $id,
    ): Announcement {
        return self::registered($title, new AnnouncementId($id));
    }

    public static function random(): Announcement
    {
        return self::fromTitle('announcement-' . rand(100, 999));
    }

    public static function draft(string $title = 'Novedad', ?AnnouncementId $id = null, ChangelogCategory $category = ChangelogCategory::Feature): Announcement
    {
        return self::registered($title, $id, $category);
    }

    public static function published(string $title = 'Novedad publicada', ?AnnouncementId $id = null, ChangelogCategory $category = ChangelogCategory::Feature): Announcement
    {
        $announcement = self::registered($title, $id, $category);
        $announcement->publish(false);

        return $announcement;
    }

    public static function scheduledFor(string $datetime, bool $notify = false, string $title = 'Novedad programada', ?AnnouncementId $id = null): Announcement
    {
        $announcement = self::registered($title, $id);
        $announcement->schedule(new \DateTimeImmutable($datetime), $notify);

        return $announcement;
    }

    public static function withTranslations(Locale ...$locales): Announcement
    {
        $default = LocaleGetter::default();
        $announcement = self::registered('Título ' . $default->value, summary: 'Resumen ' . $default->value, body: 'Contenido ' . $default->value);

        foreach ($locales as $locale) {
            if ($default === $locale) {
                continue;
            }

            $announcement->translate('Título ' . $locale->value, 'Resumen ' . $locale->value, 'Contenido ' . $locale->value, null, null, $locale);
        }

        return $announcement;
    }

    public static function withManual(Locale $locale, ?AnnouncementId $id = null): Announcement
    {
        $manualId = MediaId::generate();

        if (LocaleGetter::default() === $locale) {
            return self::registered('Novedad con manual', $id, manualId: $manualId);
        }

        $announcement = self::registered('Novedad con manual', $id);
        $announcement->translate('Novedad con manual', 'Resumen', 'Contenido', null, $manualId, $locale);

        return $announcement;
    }

    public static function withVideo(Locale $locale): Announcement
    {
        $video = YouTubeVideo::fromId('dQw4w9WgXcQ');

        if (LocaleGetter::default() === $locale) {
            return self::registered('Novedad con vídeo', youtubeVideo: $video);
        }

        $announcement = self::registered('Novedad con vídeo');
        $announcement->translate('Novedad con vídeo', 'Resumen', 'Contenido', $video, null, $locale);

        return $announcement;
    }

    public static function withDate(string $date, string $title = 'Novedad'): Announcement
    {
        return self::registered($title, date: new \DateTimeImmutable($date));
    }

    private static function registered(
        string $title,
        ?AnnouncementId $id = null,
        ChangelogCategory $category = ChangelogCategory::Feature,
        ?\DateTimeImmutable $date = null,
        ?string $summary = null,
        ?string $body = null,
        ?YouTubeVideo $youtubeVideo = null,
        ?MediaId $manualId = null,
    ): Announcement {
        $announcement = new Announcement($id ?? AnnouncementId::generate());
        $announcement->register(
            category: $category,
            date: $date ?? new \DateTimeImmutable('today'),
            version: null,
            title: $title,
            summary: $summary ?? $title . ' resumen',
            body: $body ?? '# ' . $title,
            youtubeVideo: $youtubeVideo,
            manualId: $manualId,
            registeredBy: null,
        );

        return $announcement;
    }
}
