<?php

declare(strict_types=1);

namespace App\FoodLoss\Infrastructure\Tcpdf;

use App\DataExchange\Infrastructure\Persistence\Writer\BrandedPdfBuilderTrait;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLog;
use App\FoodLoss\Domain\Model\DeliveryLog\DeliveryLogsPdfGenerator;
use App\FoodLoss\Domain\Model\RecipientOrganization\PickupType;
use App\FoodLoss\Domain\Model\RecipientOrganization\RecipientOrganizationId;
use App\FoodLoss\Infrastructure\Symfony\Twig\RecipientOrganizationExtension;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Infrastructure\Symfony\Twig\PropertyExtension;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class TcpdfDeliveryLogsPdfGenerator implements DeliveryLogsPdfGenerator
{
    use BrandedPdfBuilderTrait;

    public function __construct(
        private readonly RecipientOrganizationExtension $recipientOrganizationExtension,
        private readonly PropertyExtension $propertyExtension,
        private TranslatorInterface $translator,
        private LocaleSwitcher $localeSwitcher,
    ) {}

    /** @param DeliveryLog[] $deliveryLogs */
    public function generate(array $deliveryLogs, \DateTimeImmutable $deliverAt, PickupType $pickupType): string
    {
        $recipientOrganizationId = $deliveryLogs[0]->recipientOrganizationId();

        assert($recipientOrganizationId instanceof RecipientOrganizationId);

        $recipientOrganization = $this->recipientOrganizationExtension->recipientOrganizationName($recipientOrganizationId);
        $locale = $recipientOrganization?->contact()?->preferredLocale() ?? LocaleGetter::locale();

        return $this->localeSwitcher->runWithLocale($locale->value, function () use ($deliveryLogs, $deliverAt, $pickupType, $recipientOrganization) {
            $headers = [
                $this->translator->trans('Cantidad'),
                $this->translator->trans('Tipo'),
                $this->translator->trans('Lote'),
                $this->translator->trans('Entidad receptora'),
                $this->translator->trans('Propiedad'),
                $this->translator->trans('Fecha entrega'),
                $this->translator->trans('Tipo entrega'),
            ];

            $rows = [];

            $pickupTypeLabel = $this->translator->trans($pickupType->value, [], 'pickupType');

            foreach ($deliveryLogs as $deliveryLog) {
                $propertyId = $deliveryLog->propertyId();

                $rows[] = [
                    $deliveryLog->quantity() . ' ' . $this->translator->trans($deliveryLog->unit()->value, [], 'deliveryLogUnit'),
                    $this->translator->trans($deliveryLog->productType()->value, [], 'foodType'),
                    $deliveryLog->batch(),
                    (string) $recipientOrganization,
                    (string) ($propertyId instanceof PropertyId ? $this->propertyExtension->propertyName($propertyId) : '---'),
                    $deliverAt->format('d/m/Y'),
                    $pickupTypeLabel,
                ];
            }

            $title = $this->translator->trans('Registro de entregas');

            $pdf = $this->createBrandedPDF($title);

            $pdf->AddPage();

            $this->renderBrandedTitle($pdf, $title, $deliverAt->format('d / m / Y'));

            $this->setBrandedFont($pdf, 8, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
            $pdf->writeHTML($this->buildBrandedTableHtml($headers, $rows), true, false, true, false, '');

            $this->renderBrandedChromeOnEveryPage($pdf, mb_strtoupper($title));

            $path = sprintf('/var/pdf/delivery-logs/%s.pdf', uniqid('dl-', true));

            $dir = dirname($path);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $pdf->Output($path, 'F');

            return $path;
        });
    }
}
