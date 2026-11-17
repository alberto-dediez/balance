<?php

declare(strict_types=1);

namespace App\Tests\Unit\Changelog\Domain\Model\GuideArticle;

use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\GuideProfile;

class GuideArticleMother
{
    public static function fromProfileAndPath(
        GuideProfile $profile,
        string $path,
        string $title = 'Usuarios',
        string $body = 'Cuerpo de prueba',
        string $menu = 'Seguridad > Organización > Usuarios',
    ): GuideArticle {
        return new GuideArticle(
            profile: $profile,
            path: new GuideArticlePath($path),
            title: $title,
            summary: 'Resumen de prueba',
            menu: $menu,
            body: $body,
            updated: new \DateTimeImmutable('2026-11-01 10:00'),
        );
    }

    public static function admin(string $path = 'seguridad/organizacion/usuarios'): GuideArticle
    {
        return self::fromProfileAndPath(GuideProfile::Admin, $path);
    }
}
