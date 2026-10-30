<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Application\PropertyCarbonFootprintCertificate\Generate;

use App\Balance\Application\ContributionCertificate\Generate\CertificateLogoDownloader;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificate;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateData;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificatePdfGenerator;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateRepository;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateStorage;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprintCertificate\PropertyCarbonFootprintCertificateVerificationUrlGenerator;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Destination\DestinationId;
use App\Property\Domain\Model\Destination\DestinationRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Branding\Resolve\BrandingResolver;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Branding\PdfBranding;
use App\Shared\Domain\Model\Locale\Locale;
use App\Shared\Domain\Model\Locale\LocaleGetter;
use App\Shared\Domain\Model\Theme\ThemeRegistry;

class GeneratePropertyCarbonFootprintCertificateService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintCertificateRepository $certificateRepository,
        private PropertyRepository $propertyRepository,
        private DestinationRepository $destinationRepository,
        private CountryRepository $countryRepository,
        private PropertyCarbonFootprintCertificatePdfGenerator $pdfGenerator,
        private PropertyCarbonFootprintCertificateStorage $storage,
        private PropertyCarbonFootprintCertificateVerificationUrlGenerator $verificationUrlGenerator,
        private BrandingResolver $brandingResolver,
        private CertificateLogoDownloader $logoDownloader,
        private ThemeRegistry $themeRegistry,
        private string $localeList,
        private string $documentAuthor,
    ) {}

    public function __invoke(GeneratePropertyCarbonFootprintCertificateRequest $request, User $user): void
    {
        $certificate = $this->certificateRepository->find($request->propertyCarbonFootprintCertificateId);

        if (null === $certificate) {
            throw new \InvalidArgumentException('PropertyCarbonFootprintCertificate not found');
        }

        if ($certificate->isGenerated() || $certificate->isRevoked()) {
            return;
        }

        $locales = Locale::listFrom($this->localeList);

        if ([] === $locales) {
            throw new \RuntimeException('No locales configured');
        }

        $propertyId = $certificate->propertyId();
        $property = $propertyId instanceof PropertyId ? $this->propertyRepository->find($propertyId) : null;

        $tokens = $this->brandingResolver->resolve($certificate->accountId());
        $logoPath = $this->logoDownloader->download($tokens->logoMediaId);
        $theme = $this->themeRegistry->get($tokens->themeSlug);
        $branding = PdfBranding::fromTokens($tokens, $this->documentAuthor, $logoPath)->withThemeLogos($theme->logo(), $theme->inverseLogo());

        $previousLocale = LocaleGetter::locale();
        $files = [];

        try {
            foreach ($locales as $locale) {
                LocaleGetter::setLocale($locale);

                $data = $this->composeData($certificate, $property, $locale);

                $localPath = $this->pdfGenerator->generate($data, $locale, $branding);

                try {
                    $files[$locale->value] = $this->storage->store(
                        $certificate->id(),
                        $certificate->accountId(),
                        $locale,
                        $localPath,
                    );
                } finally {
                    @unlink($localPath);
                }
            }

            if ($this->certificateRepository->isRevoked($certificate->id())) {
                foreach ($files as $storedPath) {
                    $this->storage->delete($storedPath);
                }

                return;
            }

            $certificate->markGenerated($files);
        } catch (\Throwable $e) {
            foreach ($files as $storedPath) {
                $this->storage->delete($storedPath);
            }

            if ($this->certificateRepository->isRevoked($certificate->id())) {
                throw $e;
            }

            $certificate->markFailed($e->getMessage());
            $this->certificateRepository->add($certificate);

            throw $e;
        } finally {
            LocaleGetter::setLocale($previousLocale);

            if (null !== $logoPath) {
                @unlink($logoPath);
            }
        }

        $this->certificateRepository->add($certificate);
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof GeneratePropertyCarbonFootprintCertificateRequest);
        $this->__invoke($command, $user);

        return null;
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof GeneratePropertyCarbonFootprintCertificateRequest;
    }

    private function composeData(
        PropertyCarbonFootprintCertificate $certificate,
        ?Property $property,
        Locale $locale,
    ): PropertyCarbonFootprintCertificateData {
        return new PropertyCarbonFootprintCertificateData(
            code: $certificate->code(),
            propertyName: $certificate->propertyName(),
            propertyAddress: $property instanceof Property ? $this->propertyAddress($property) : null,
            accountName: $certificate->accountName(),
            year: $certificate->year(),
            stays: $certificate->stays(),
            co2eqKgPerStay: $certificate->co2eqKgPerStay(),
            totalCo2eqKg: $certificate->totalCo2eqKg(),
            scope1Co2eqKg: $certificate->scope1Co2eqKg(),
            scope2Co2eqKg: $certificate->scope2Co2eqKg(),
            scope3Co2eqKg: $certificate->scope3Co2eqKg(),
            issuedAt: $certificate->issuedAt(),
            verificationUrl: $this->verificationUrlGenerator->generate($certificate->verificationToken(), $locale),
        );
    }

    private function propertyAddress(Property $property): ?string
    {
        $info = $property->info();

        $destinationId = $info->destinationId();
        $countryId = $info->countryId();

        $parts = array_filter(
            [
                $info->address(),
                $info->postalCode(),
                $destinationId instanceof DestinationId ? $this->destinationRepository->find($destinationId)?->name() : null,
                $countryId instanceof CountryId ? $this->countryRepository->find($countryId)?->name() : null,
            ],
            static fn(?string $part): bool => null !== $part && '' !== trim($part),
        );

        return [] === $parts ? null : implode(', ', $parts);
    }
}
