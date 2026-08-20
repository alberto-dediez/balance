<?php

declare(strict_types=1);

namespace App\Security\Domain\Model\Branding;

use App\Library\Domain\Model\Media\MediaId;
use App\Security\Domain\DomainEvent;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Domain\Event\DomainEventId;
use App\Shared\Domain\Model\AggregateRootId;

final class BrandingChanged extends DomainEvent
{
    protected static string $eventName = 'brandingChanged';

    public function __construct(
        private readonly BrandingId $brandingId,
        private readonly AccountId $accountId,
        private readonly ?MediaId $logoMediaId,
        private readonly ?HexColor $primary,
        private readonly ?HexColor $secondary,
        private readonly ?HexColor $success,
        private readonly ?HexColor $info,
        private readonly ?HexColor $warning,
        private readonly ?HexColor $danger,
        private readonly ?HexColor $light,
        private readonly ?HexColor $dark,
        private readonly ?HexColor $bodyColor,
        private readonly ?HexColor $bodyBg,
        private readonly ?HexColor $primaryHover,
        private readonly ?HexColor $primarySubtle,
        private readonly ?HexColor $secondaryHover,
        private readonly ?HexColor $secondarySubtle,
        private readonly ?FontFamilyName $webFontFamily,
        private readonly ?string $webFontWeights,
        private readonly PdfFontFamily $pdfFontFamily,
        ?DomainEventId $id = null,
        ?\DateTimeImmutable $occurredOn = null,
    ) {
        parent::__construct($id, $occurredOn);
    }

    public function brandingId(): BrandingId
    {
        return $this->brandingId;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
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

    public function aggregateRootId(): AggregateRootId
    {
        return $this->brandingId;
    }

    public function payload(): array
    {
        return [
            'brandingId' => (string) $this->brandingId,
            'accountId' => (string) $this->accountId,
            'logoMediaId' => $this->logoMediaId instanceof MediaId ? (string) $this->logoMediaId : null,
            'primary' => $this->primary instanceof HexColor ? $this->primary->value : null,
            'secondary' => $this->secondary instanceof HexColor ? $this->secondary->value : null,
            'success' => $this->success instanceof HexColor ? $this->success->value : null,
            'info' => $this->info instanceof HexColor ? $this->info->value : null,
            'warning' => $this->warning instanceof HexColor ? $this->warning->value : null,
            'danger' => $this->danger instanceof HexColor ? $this->danger->value : null,
            'light' => $this->light instanceof HexColor ? $this->light->value : null,
            'dark' => $this->dark instanceof HexColor ? $this->dark->value : null,
            'bodyColor' => $this->bodyColor instanceof HexColor ? $this->bodyColor->value : null,
            'bodyBg' => $this->bodyBg instanceof HexColor ? $this->bodyBg->value : null,
            'primaryHover' => $this->primaryHover instanceof HexColor ? $this->primaryHover->value : null,
            'primarySubtle' => $this->primarySubtle instanceof HexColor ? $this->primarySubtle->value : null,
            'secondaryHover' => $this->secondaryHover instanceof HexColor ? $this->secondaryHover->value : null,
            'secondarySubtle' => $this->secondarySubtle instanceof HexColor ? $this->secondarySubtle->value : null,
            'webFontFamily' => $this->webFontFamily instanceof FontFamilyName ? $this->webFontFamily->value : null,
            'webFontWeights' => $this->webFontWeights,
            'pdfFontFamily' => $this->pdfFontFamily->value,
        ];
    }

    public static function rebuildFromPayload(
        DomainEventId $id,
        \DateTimeImmutable $occurredOn,
        array $payload = [],
    ): static {
        return new BrandingChanged(
            brandingId: new BrandingId($payload['brandingId']),
            accountId: new AccountId($payload['accountId']),
            logoMediaId: null !== ($payload['logoMediaId'] ?? null) ? new MediaId($payload['logoMediaId']) : null,
            primary: null !== ($payload['primary'] ?? null) ? new HexColor($payload['primary']) : null,
            secondary: null !== ($payload['secondary'] ?? null) ? new HexColor($payload['secondary']) : null,
            success: null !== ($payload['success'] ?? null) ? new HexColor($payload['success']) : null,
            info: null !== ($payload['info'] ?? null) ? new HexColor($payload['info']) : null,
            warning: null !== ($payload['warning'] ?? null) ? new HexColor($payload['warning']) : null,
            danger: null !== ($payload['danger'] ?? null) ? new HexColor($payload['danger']) : null,
            light: null !== ($payload['light'] ?? null) ? new HexColor($payload['light']) : null,
            dark: null !== ($payload['dark'] ?? null) ? new HexColor($payload['dark']) : null,
            bodyColor: null !== ($payload['bodyColor'] ?? null) ? new HexColor($payload['bodyColor']) : null,
            bodyBg: null !== ($payload['bodyBg'] ?? null) ? new HexColor($payload['bodyBg']) : null,
            primaryHover: null !== ($payload['primaryHover'] ?? null) ? new HexColor($payload['primaryHover']) : null,
            primarySubtle: null !== ($payload['primarySubtle'] ?? null) ? new HexColor($payload['primarySubtle']) : null,
            secondaryHover: null !== ($payload['secondaryHover'] ?? null) ? new HexColor($payload['secondaryHover']) : null,
            secondarySubtle: null !== ($payload['secondarySubtle'] ?? null) ? new HexColor($payload['secondarySubtle']) : null,
            webFontFamily: null !== ($payload['webFontFamily'] ?? null) ? new FontFamilyName($payload['webFontFamily']) : null,
            webFontWeights: $payload['webFontWeights'] ?? null,
            pdfFontFamily: PdfFontFamily::from($payload['pdfFontFamily']),
            id: $id,
            occurredOn: $occurredOn,
        );
    }
}
