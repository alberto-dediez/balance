<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\UI\Web\AccountTripCalculator;

use App\CarbonFootprint\Application\Trip\TripDestinationCandidates;
use App\CarbonFootprint\Application\TripCalculationLink\UpdateDestination\UpdateTripCalculationLinkDestinationRequest;
use App\CarbonFootprint\Application\TripCalculationLink\UpdateDestination\UpdateTripCalculationLinkDestinationService;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLink;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripDestinationMode;
use App\CarbonFootprint\Infrastructure\Symfony\Form\UpdateTripCalculationLinkDestinationType;
use App\Property\Domain\Model\Country\Country;
use App\Property\Domain\Model\Country\CountryFilter;
use App\Property\Domain\Model\Country\CountryId;
use App\Property\Domain\Model\Country\CountryRepository;
use App\Property\Domain\Model\Property\Property;
use App\Property\Domain\Model\Property\PropertyId;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Domain\Model\Account\AccountRepository;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Place\CountryRegionCode;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class TripCalculationDestinationForm
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private RequestStack $requestStack,
        private TranslatorInterface $translator,
        private CountryRepository $countryRepository,
        private TripDestinationCandidates $tripDestinationCandidates,
        private AccountRepository $accountRepository,
        private CountryRegionCode $countryRegionCode,
        private UpdateTripCalculationLinkDestinationService $updateTripCalculationLinkDestination,
    ) {}

    /** @return FormInterface<TripCalculationDestinationFormData> */
    public function handle(TripCalculationLink $tripCalculationLink, User $user, Request $request): FormInterface
    {
        $form = $this->formFactory->create(
            UpdateTripCalculationLinkDestinationType::class,
            new TripCalculationDestinationFormData(
                mode: $tripCalculationLink->destinationMode(),
                placeKind: $tripCalculationLink->destinationPlaceKind(),
                countryIds: array_map(static fn(CountryId $countryId): string => (string) $countryId, $tripCalculationLink->destinationCountryIds()),
                propertyIds: array_map(static fn(PropertyId $propertyId): string => (string) $propertyId, $tripCalculationLink->destinationPropertyIds()),
                regionReference: $tripCalculationLink->destinationRegionReference(),
                regionLabel: $tripCalculationLink->destinationRegionLabel(),
            ),
            [
                'countries' => $this->countryChoices(),
                'properties' => $this->propertyChoices($tripCalculationLink),
                'region_label' => $tripCalculationLink->destinationRegionLabel(),
            ],
        );

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $form;
        }

        $data = $form->getData();

        try {
            ($this->updateTripCalculationLinkDestination)(
                new UpdateTripCalculationLinkDestinationRequest(
                    tripCalculationLinkId: $tripCalculationLink->id(),
                    mode: $data->mode,
                    placeKind: $data->placeKind,
                    countryIds: array_map(static fn(string $countryId): CountryId => new CountryId($countryId), $data->countryIds),
                    propertyIds: array_map(static fn(string $propertyId): PropertyId => new PropertyId($propertyId), $data->propertyIds),
                    regionReference: $data->regionReference,
                ),
                $user,
            );
        } catch (\InvalidArgumentException $e) {
            $this->fieldFor($form, $data->mode)->addError(new FormError($this->translator->trans($e->getMessage())));

            return $form;
        }

        $this->addSuccessFlash();

        return $form;
    }

    /** @return array<string, string> */
    private function countryChoices(): array
    {
        $choices = [];

        foreach ($this->countryRepository->findByFilter(new CountryFilter(limit: 0)) as $country) {
            if (!$this->hasIso($country)) {
                continue;
            }

            $choices[$country->name()] = (string) $country->id();
        }

        ksort($choices);

        return $choices;
    }

    private function hasIso(Country $country): bool
    {
        $iso = $country->iso();

        return null !== $iso && null !== $this->countryRegionCode->alpha2($iso);
    }

    /** @return array<string, string> */
    private function propertyChoices(TripCalculationLink $tripCalculationLink): array
    {
        $accountId = $tripCalculationLink->accountId();

        if (null === $accountId) {
            return [];
        }

        $properties = $this->tripDestinationCandidates->candidatesFor($accountId);
        $nameCounts = array_count_values(array_map(static fn(Property $property): string => $property->name(), $properties));
        $choices = [];

        foreach ($properties as $property) {
            $choices[$this->uniqueLabel($this->propertyLabel($property, $accountId, $nameCounts[$property->name()] > 1), $choices)] = (string) $property->id();
        }

        return $choices;
    }

    /** @param array<string, string> $choices */
    private function uniqueLabel(string $label, array $choices): string
    {
        $uniqueLabel = $label;
        $index = 1;

        while (array_key_exists($uniqueLabel, $choices)) {
            $uniqueLabel = sprintf('%s (%d)', $label, ++$index);
        }

        return $uniqueLabel;
    }

    private function propertyLabel(Property $property, AccountId $accountId, bool $repeatedName): string
    {
        $propertyAccountId = $property->accountId();

        if (null === $propertyAccountId || (!$repeatedName && $propertyAccountId->equals($accountId))) {
            return $property->name();
        }

        $accountName = $this->accountRepository->find($propertyAccountId)?->name();

        return null !== $accountName ? sprintf('%s (%s)', $property->name(), $accountName) : $property->name();
    }

    /**
     * @param FormInterface<TripCalculationDestinationFormData> $form
     *
     * @return FormInterface<mixed>
     */
    private function fieldFor(FormInterface $form, TripDestinationMode $mode): FormInterface
    {
        return match ($mode) {
            TripDestinationMode::Countries => $form->get('countryIds'),
            TripDestinationMode::Region => $form->get('regionReference'),
            TripDestinationMode::SelectedProperties => $form->get('propertyIds'),
            default => $form,
        };
    }

    private function addSuccessFlash(): void
    {
        $session = $this->requestStack->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('success', new TranslatableMessage('Calculadora actualizada con éxito'));
        }
    }
}
