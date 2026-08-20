<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Model\AggregateRoot;

class Branding
{
    use AggregateRoot;

    /**
     * @param DomainEvent[] $events
     */
    public function __construct(
        protected BrandingId $id,
        protected AccountId $accountId,
        protected string $themeSlug = 'default',
        protected ?MediaId $logoMediaId = null,
        protected ?HexColor $primary = null,
        protected ?HexColor $secondary = null,
        protected ?HexColor $success = null,
        protected ?HexColor $info = null,
        protected ?HexColor $warning = null,
        protected ?HexColor $danger = null,
        protected ?HexColor $light = null,
        protected ?HexColor $dark = null,
        protected ?HexColor $bodyColor = null,
        protected ?HexColor $bodyBg = null,
        protected ?HexColor $primaryHover = null,
        protected ?HexColor $primarySubtle = null,
        protected ?HexColor $secondaryHover = null,
        protected ?HexColor $secondarySubtle = null,
        protected ?FontFamilyName $webFontFamily = null,
        protected ?string $webFontWeights = null,
        protected PdfFontFamily $pdfFontFamily = PdfFontFamily::Rubik,
        protected bool $removed = false,
        array $events = [],
        protected \DateTimeImmutable $registeredAt = new \DateTimeImmutable('NOW'),
        protected ?\DateTimeImmutable $removedAt = null,
    ) {
        $this->events = $events;
    }

    public function id(): BrandingId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function themeSlug(): string
    {
        return $this->themeSlug;
    }

    public function logoMediaId(): ?MediaId
    {
        return $this->logoMediaId;
    }

    public function primary(): ?HexColor
    {
        return $this->primary;
    }

    public function secondary(): ?HexColor
    {
        return $this->secondary;
    }

    public function success(): ?HexColor
    {
        return $this->success;
    }

    public function info(): ?HexColor
    {
        return $this->info;
    }

    public function warning(): ?HexColor
    {
        return $this->warning;
    }

    public function danger(): ?HexColor
    {
        return $this->danger;
    }

    public function light(): ?HexColor
    {
        return $this->light;
    }

    public function dark(): ?HexColor
    {
        return $this->dark;
    }

    public function bodyColor(): ?HexColor
    {
        return $this->bodyColor;
    }

    public function bodyBg(): ?HexColor
    {
        return $this->bodyBg;
    }

    public function primaryHover(): ?HexColor
    {
        return $this->primaryHover;
    }

    public function primarySubtle(): ?HexColor
    {
        return $this->primarySubtle;
    }

    public function secondaryHover(): ?HexColor
    {
        return $this->secondaryHover;
    }

    public function secondarySubtle(): ?HexColor
    {
        return $this->secondarySubtle;
    }

    public function webFontFamily(): ?FontFamilyName
    {
        return $this->webFontFamily;
    }

    public function webFontWeights(): ?string
    {
        return $this->webFontWeights;
    }

    public function pdfFontFamily(): PdfFontFamily
    {
        return $this->pdfFontFamily;
    }

    public function isRemoved(): bool
    {
        return $this->removed;
    }

    public function registeredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function removedAt(): ?\DateTimeImmutable
    {
        return $this->removedAt;
    }

    public function isCustomised(): bool
    {
        if ($this->logoMediaId instanceof MediaId || $this->webFontFamily instanceof FontFamilyName || 'default' !== $this->themeSlug) {
            return true;
        }

        foreach ($this->colors() as $color) {
            if ($color instanceof HexColor) {
                return true;
            }
        }

        return false;
    }

    /** @return list<?HexColor> */
    private function colors(): array
    {
        return [
            $this->primary,
            $this->secondary,
            $this->success,
            $this->info,
            $this->warning,
            $this->danger,
            $this->light,
            $this->dark,
            $this->bodyColor,
            $this->bodyBg,
            $this->primaryHover,
            $this->primarySubtle,
            $this->secondaryHover,
            $this->secondarySubtle,
        ];
    }

    public function register(string $themeSlug = 'default'): void
    {
        $this->recordApplyAndPublish(
            new BrandingRegistered(
                brandingId: $this->id(),
                accountId: $this->accountId(),
                themeSlug: $themeSlug,
            ),
        );
    }

    public function change(
        ?MediaId $logoMediaId,
        ?HexColor $primary,
        ?HexColor $secondary,
        ?HexColor $success,
        ?HexColor $info,
        ?HexColor $warning,
        ?HexColor $danger,
        ?HexColor $light,
        ?HexColor $dark,
        ?HexColor $bodyColor,
        ?HexColor $bodyBg,
        ?HexColor $primaryHover,
        ?HexColor $primarySubtle,
        ?HexColor $secondaryHover,
        ?HexColor $secondarySubtle,
        ?FontFamilyName $webFontFamily,
        ?string $webFontWeights,
        PdfFontFamily $pdfFontFamily,
    ): void {
        $this->recordApplyAndPublish(
            new BrandingChanged(
                brandingId: $this->id(),
                accountId: $this->accountId(),
                logoMediaId: $logoMediaId,
                primary: $primary,
                secondary: $secondary,
                success: $success,
                info: $info,
                warning: $warning,
                danger: $danger,
                light: $light,
                dark: $dark,
                bodyColor: $bodyColor,
                bodyBg: $bodyBg,
                primaryHover: $primaryHover,
                primarySubtle: $primarySubtle,
                secondaryHover: $secondaryHover,
                secondarySubtle: $secondarySubtle,
                webFontFamily: $webFontFamily,
                webFontWeights: $webFontWeights,
                pdfFontFamily: $pdfFontFamily,
            ),
        );
    }

    public function changeTheme(string $themeSlug): void
    {
        $this->recordApplyAndPublish(
            new BrandingThemeChanged(
                brandingId: $this->id(),
                accountId: $this->accountId(),
                themeSlug: $themeSlug,
            ),
        );
    }

    public function remove(): void
    {
        $this->recordApplyAndPublish(
            new BrandingRemoved(
                brandingId: $this->id(),
                accountId: $this->accountId(),
            ),
        );
    }

    public function applyBrandingRegistered(BrandingRegistered $event): void
    {
        $this->themeSlug = $event->themeSlug();

        $this->registeredAt = $event->occurredOn();
    }

    public function applyBrandingChanged(BrandingChanged $event): void
    {
        $this->logoMediaId = $event->logoMediaId();
        $this->primary = $event->primary();
        $this->secondary = $event->secondary();
        $this->success = $event->success();
        $this->info = $event->info();
        $this->warning = $event->warning();
        $this->danger = $event->danger();
        $this->light = $event->light();
        $this->dark = $event->dark();
        $this->bodyColor = $event->bodyColor();
        $this->bodyBg = $event->bodyBg();
        $this->primaryHover = $event->primaryHover();
        $this->primarySubtle = $event->primarySubtle();
        $this->secondaryHover = $event->secondaryHover();
        $this->secondarySubtle = $event->secondarySubtle();
        $this->webFontFamily = $event->webFontFamily();
        $this->webFontWeights = $event->webFontWeights();
        $this->pdfFontFamily = $event->pdfFontFamily();
    }

    public function applyBrandingThemeChanged(BrandingThemeChanged $event): void
    {
        $this->themeSlug = $event->themeSlug();
    }

    public function applyBrandingRemoved(BrandingRemoved $event): void
    {
        $this->removed = true;
        $this->removedAt = new \DateTimeImmutable('NOW');
    }
}
