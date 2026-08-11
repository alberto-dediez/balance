<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Tcpdf;

use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificateData;
use App\Balance\Domain\Model\ContributionCertificate\ContributionCertificatePdfGenerator;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Infrastructure\Qr\QrCodeGenerator;
use App\Shared\Infrastructure\Tcpdf\GenericGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * One page, two documents. The compensation variant states the volume offset, the lots behind it and
 * a QR pointing at the public verification page; the social one deliberately carries none of that,
 * nor any wording about offsetting or neutrality.
 */
final class TcpdfContributionCertificatePdfGenerator implements ContributionCertificatePdfGenerator
{
    use GenericGenerator;

    private const float QR_SIZE = 32.0;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly QrCodeGenerator $qrCodeGenerator,
    ) {}

    public function generate(ContributionCertificateData $data, Locale $locale): string
    {
        $previousLocale = LocaleGetter::locale();
        LocaleGetter::setLocale($locale);

        try {
            $documentName = $this->documentName($data, $locale);

            $pdf = $this->createPDF($documentName);
            $this->startContentPages($pdf, $documentName);

            $pdf->AddPage();
            $this->renderSectionTitle($pdf, $documentName);

            $this->renderIssuerBlock($pdf, $data, $locale);
            $this->renderBeneficiaryBlock($pdf, $data, $locale);
            $this->renderContributionBlock($pdf, $data, $locale);
            $this->renderProjectBlock($pdf, $data, $locale);
            $this->renderSerialsBlock($pdf, $data, $locale);
            $this->renderVerificationBlock($pdf, $data, $locale);
            $this->renderLegalNotice($pdf, $data, $locale);

            $path = sys_get_temp_dir() . '/' . uniqid('contribution-certificate-', true) . '-' . $locale->value . '.pdf';
            $this->savePDF($pdf, $path);

            return $path;
        } finally {
            LocaleGetter::setLocale($previousLocale);
        }
    }

    private function documentName(ContributionCertificateData $data, Locale $locale): string
    {
        return $data->type->isCompensation()
            ? $this->trans('Certificado de Compensación de Carbono', $locale)
            : $this->trans('Certificado de Aportación Social', $locale);
    }

    private function renderIssuerBlock(\TCPDF $pdf, ContributionCertificateData $data, Locale $locale): void
    {
        $this->renderTable($pdf, $this->trans('Emisor', $locale), [
            [$this->trans('Entidad emisora', $locale), 'Bioscore Sustainability'],
            [$this->trans('Fecha de emisión', $locale), $data->issuedAt->format('d/m/Y')],
            [$this->trans('Código del certificado', $locale), $data->code],
        ], $locale);
    }

    private function renderBeneficiaryBlock(\TCPDF $pdf, ContributionCertificateData $data, Locale $locale): void
    {
        $rows = [
            [
                $data->beneficiaryIsCompany ? $this->trans('Razón social', $locale) : $this->trans('Nombre', $locale),
                $data->beneficiaryName,
            ],
        ];

        if (null !== $data->beneficiaryVatNumber && '' !== $data->beneficiaryVatNumber) {
            $rows[] = [$this->trans('NIF / NIF-IVA', $locale), $data->beneficiaryVatNumber];
        }

        if ($data->beneficiaryIsCompany) {
            $rows[] = [$this->trans('Persona de contacto', $locale), $data->contactName];
        }

        $rows[] = [$this->trans('País', $locale), $data->contributorCountry];

        $this->renderTable($pdf, $this->trans('Beneficiario', $locale), $rows, $locale);
    }

    private function renderContributionBlock(\TCPDF $pdf, ContributionCertificateData $data, Locale $locale): void
    {
        $rows = [];

        if ($data->type->isCompensation() && null !== $data->co2eqKg) {
            $rows[] = [
                $this->trans('Emisiones compensadas', $locale),
                sprintf('%s kg CO₂e', number_format($data->co2eqKg, 2, ',', '.')),
            ];
        }

        $rows[] = [
            $data->type->isCompensation()
                ? $this->trans('Aportación al proyecto', $locale)
                : $this->trans('Aportación recibida', $locale),
            sprintf('%s %s', number_format($data->contributionAmount, 2, ',', '.'), $data->currency),
        ];

        $this->renderTable($pdf, $this->trans('Aportación', $locale), $rows, $locale);
    }

    private function renderProjectBlock(\TCPDF $pdf, ContributionCertificateData $data, Locale $locale): void
    {
        $rows = [[$this->trans('Proyecto', $locale), $data->projectName]];

        if (null !== $data->projectLocation && '' !== $data->projectLocation) {
            $rows[] = [$this->trans('Ubicación', $locale), $data->projectLocation];
        }

        if ($data->type->isCompensation() && null !== $data->projectRegistryId && '' !== $data->projectRegistryId) {
            $rows[] = [$this->trans('Registro oficial', $locale), $data->projectRegistryId];
        }

        $this->renderTable($pdf, $this->trans('Proyecto', $locale), $rows, $locale);
    }

    private function renderSerialsBlock(\TCPDF $pdf, ContributionCertificateData $data, Locale $locale): void
    {
        if (!$data->type->isCompensation() || [] === $data->masterSerials) {
            return;
        }

        $rows = array_map(
            static fn(string $serial): array => [$serial],
            $data->masterSerials,
        );

        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->writeHTML(
            $this->buildTableHtml([$this->trans('Números de serie maestros', $locale)], $rows),
            true,
            false,
            true,
            false,
            '',
        );
        $pdf->setY($pdf->GetY() + 4);
    }

    private function renderVerificationBlock(\TCPDF $pdf, ContributionCertificateData $data, Locale $locale): void
    {
        if (!$data->type->isCompensation() || null === $data->verificationUrl) {
            return;
        }

        $binary = $this->qrPng($data->verificationUrl);

        if (null === $binary) {
            return;
        }

        if ($pdf->GetY() > $pdf->getPageHeight() - (self::QR_SIZE + 40)) {
            $pdf->AddPage();
        }

        $top = $pdf->GetY() + 4;

        $pdf->Image('@' . $binary, self::CONTENT_MARGIN_LEFT, $top, self::QR_SIZE, self::QR_SIZE, 'PNG');

        $this->setFont($pdf, 9, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->setXY(self::CONTENT_MARGIN_LEFT + self::QR_SIZE + 6, $top);
        $pdf->MultiCell(
            self::CONTENT_WIDTH - self::QR_SIZE - 6,
            5,
            // The link carries the verification secret, so it is only offered as a QR: written out
            // it would be unusable to type and one more place for the token to leak from.
            $this->trans('Escanea este código QR para verificar el certificado.', $locale) . "\n"
                . $this->trans('La referencia mostrada debe coincidir con la de este documento:', $locale)
                . ' ' . $data->code,
            0,
            'L',
        );

        $pdf->setY(max($pdf->GetY(), $top + self::QR_SIZE) + 6);
    }

    private function renderLegalNotice(\TCPDF $pdf, ContributionCertificateData $data, Locale $locale): void
    {
        $notice = $data->type->isCompensation()
            ? $this->trans('Este certificado acredita la retirada de créditos de carbono verificados por los lotes indicados. Los créditos consumidos quedan trazados y no pueden volver a utilizarse ni contabilizarse por ninguna otra parte.', $locale)
            : $this->trans('Este documento acredita la recepción de la aportación económica indicada y su destino al proyecto descrito. No acredita la reducción, la retirada ni la compensación de emisiones de gases de efecto invernadero.', $locale);

        $this->setFont($pdf, 8, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);
        $pdf->setX(self::CONTENT_MARGIN_LEFT);
        $pdf->MultiCell(self::CONTENT_WIDTH, 4, $notice, 0, 'L');
    }

    /** @param list<array{0: string, 1: string}> $rows */
    private function renderTable(\TCPDF $pdf, string $title, array $rows, Locale $locale): void
    {
        if ([] === $rows) {
            return;
        }

        $this->setFont($pdf, 10, self::FONT_STYLE_REGULAR, self::COLOR_BODY_TEXT);

        $html = $this->buildTableHtml(
            [$title, $this->trans('Valor', $locale)],
            array_map(static fn(array $row): array => [$row[0], $row[1]], $rows),
            ['40%', '60%'],
        );

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->setY($pdf->GetY() + 4);
    }

    /** The generator hands back a data URI; TCPDF wants the raw bytes behind it. */
    private function qrPng(string $url): ?string
    {
        $dataUri = $this->qrCodeGenerator->generate($url)->path;

        $comma = strpos($dataUri, ',');

        if (false === $comma) {
            return null;
        }

        $binary = base64_decode(substr($dataUri, $comma + 1), true);

        return false !== $binary ? $binary : null;
    }

    private function trans(string $id, Locale $locale): string
    {
        return $this->translator->trans($id, [], 'messages', $locale->value);
    }
}
