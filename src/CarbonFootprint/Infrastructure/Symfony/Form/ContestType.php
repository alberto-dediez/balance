<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Domain\Model\Contest\ContestAwardMode;
use App\CarbonFootprint\Domain\Model\Contest\ContestParticipationMode;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationLinkRepository;
use App\CarbonFootprint\Infrastructure\UI\Web\Contest\ContestCountries;
use App\CarbonFootprint\Infrastructure\UI\Web\Contest\ContestFormData;
use App\EventOperations\Domain\Model\Event\EventFilter;
use App\EventOperations\Domain\Model\Event\EventRepository;
use App\Property\Domain\Model\Property\PropertyFilter;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Application\Account\AccountFeaturesPolicy;
use App\Security\Domain\Model\Account\AccountFeature;
use App\Security\Domain\Model\Account\AccountId;
use App\Shared\Application\CurrentAccount\CurrentAccountIdProvider;
use App\Shared\Application\CurrentTimezone\CurrentTimezoneProvider;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use App\Shared\Infrastructure\Symfony\Form\Type\MarkdownEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<ContestFormData> */
class ContestType extends AbstractType
{
    public const string MODE_FULL = 'full';
    public const string MODE_PUBLISHED = 'published';
    public const string MODE_READONLY = 'readonly';

    public function __construct(
        private readonly CurrentTimezoneProvider $timezoneProvider,
        private readonly CurrentAccountIdProvider $currentAccountIdProvider,
        private readonly AccountFeaturesPolicy $accountFeaturesPolicy,
        private readonly EventRepository $eventRepository,
        private readonly PropertyRepository $propertyRepository,
        private readonly TripCalculationLinkRepository $tripCalculationLinkRepository,
        private readonly ContestCountries $contestCountries,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $mode = $options['mode'];

        if (self::MODE_READONLY === $mode) {
            return;
        }

        $this->addHosts($builder);

        if (self::MODE_FULL === $mode) {
            $this->addMechanics($builder);
            $builder->add('startsAt', DateTimeType::class, $this->dateOptions('Inicio'));
        }

        $builder
            ->add('endsAt', DateTimeType::class, $this->dateOptions('Fin'))
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('prizeDescription', TextType::class, [
                'label' => new TranslatableMessage('Premio'),
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('description', MarkdownEditorType::class, [
                'label' => new TranslatableMessage('Descripción'),
                'required' => false,
                'empty_data' => '',
                'height' => '200px',
            ])
            ->add('terms', MarkdownEditorType::class, [
                'label' => new TranslatableMessage('Bases'),
                'required' => false,
                'empty_data' => '',
                'height' => '300px',
            ])
            ->add('organizerName', TextType::class, [
                'label' => new TranslatableMessage('Nombre o razón social del organizador'),
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('organizerTaxId', TextType::class, [
                'label' => new TranslatableMessage('NIF del organizador'),
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 40)],
            ])
            ->add('organizerContactEmail', EmailType::class, [
                'label' => new TranslatableMessage('Email de contacto del organizador'),
                'empty_data' => '',
                'constraints' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 255)],
            ])
            ->add('privacyPolicyUrl', UrlType::class, [
                'label' => new TranslatableMessage('Política de privacidad del organizador'),
                'required' => false,
                'default_protocol' => 'https',
                'constraints' => [new Assert\Url(requireTld: true), new Assert\Length(max: 255)],
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContestFormData::class,
            'mode' => self::MODE_FULL,
        ]);

        $resolver->setAllowedValues('mode', [self::MODE_FULL, self::MODE_PUBLISHED, self::MODE_READONLY]);
    }

    /** @param FormBuilderInterface<ContestFormData|null> $builder */
    private function addHosts(FormBuilderInterface $builder): void
    {
        $accountId = $this->currentAccountIdProvider->currentAccountId();

        if (!$accountId instanceof AccountId) {
            return;
        }

        if (
            $this->accountFeaturesPolicy->isEnabled(AccountFeature::AccountTripCalculator, $accountId)
            && null !== $this->tripCalculationLinkRepository->findForAccount($accountId)
        ) {
            $builder->add('accountTripCalculator', CheckboxType::class, [
                'label' => new TranslatableMessage('Calculadora de viajes de la cuenta'),
                'required' => false,
            ]);
        }

        if ($this->accountFeaturesPolicy->isEnabled(AccountFeature::Events, $accountId)) {
            $builder->add('eventIds', AutocompleteType::class, [
                'label' => new TranslatableMessage('Encuestas de asistentes de los eventos'),
                'choices' => $this->eventChoices($accountId),
                'choice_translation_domain' => false,
                'multiple' => true,
                'required' => false,
            ]);
        }

        if ($this->accountFeaturesPolicy->isEnabled(AccountFeature::PropertyTripCalculators, $accountId)) {
            $builder->add('propertyIds', AutocompleteType::class, [
                'label' => new TranslatableMessage('Calculadoras de las propiedades'),
                'choices' => $this->propertyChoices($accountId),
                'choice_translation_domain' => false,
                'multiple' => true,
                'required' => false,
            ]);
        }
    }

    /** @param FormBuilderInterface<ContestFormData|null> $builder */
    private function addMechanics(FormBuilderInterface $builder): void
    {
        $builder
            ->add('participationMode', EnumType::class, [
                'label' => new TranslatableMessage('Participación'),
                'class' => ContestParticipationMode::class,
                'choice_label' => static fn(ContestParticipationMode $mode): TranslatableMessage => new TranslatableMessage($mode->value, [], 'contestParticipationMode'),
                'attr' => ['data-contest-form-target' => 'participation', 'data-action' => 'contest-form#refresh'],
            ])
            ->add('awardMode', EnumType::class, [
                'label' => new TranslatableMessage('Premio'),
                'class' => ContestAwardMode::class,
                'choice_label' => static fn(ContestAwardMode $mode): TranslatableMessage => new TranslatableMessage($mode->value, [], 'contestAwardMode'),
                'attr' => ['data-contest-form-target' => 'award', 'data-action' => 'contest-form#refresh'],
            ])
            ->add('winnersCount', IntegerType::class, [
                'label' => new TranslatableMessage('Número de premiados'),
                'required' => false,
                'attr' => ['min' => 1],
                'row_attr' => ['data-contest-form-target' => 'winnersCount'],
                'constraints' => [new Assert\Positive()],
            ])
            ->add('platformDraw', CheckboxType::class, [
                'label' => new TranslatableMessage('Realizar el sorteo en la plataforma'),
                'required' => false,
                'row_attr' => ['data-contest-form-target' => 'platformDraw'],
            ])
            ->add('adultsOnly', CheckboxType::class, [
                'label' => new TranslatableMessage('Solo mayores de edad'),
                'required' => false,
            ])
            ->add('allowedCountries', AutocompleteType::class, [
                'label' => new TranslatableMessage('Países admitidos'),
                'help' => new TranslatableMessage('Vacío = cualquier país'),
                'choices' => array_flip($this->contestCountries->countries()),
                'choice_translation_domain' => false,
                'multiple' => true,
                'required' => false,
            ])
        ;
    }

    /** @return array<string, string> */
    private function eventChoices(AccountId $accountId): array
    {
        $choices = [];

        foreach ($this->eventRepository->findByFilter(new EventFilter(accountId: $accountId, limit: 0)) as $event) {
            $choices[$this->uniqueLabel($event->name(), $choices)] = (string) $event->id();
        }

        return $choices;
    }

    /** @return array<string, string> */
    private function propertyChoices(AccountId $accountId): array
    {
        $choices = [];

        foreach ($this->propertyRepository->findByFilter(new PropertyFilter(accountId: $accountId, limit: 0)) as $property) {
            $choices[$this->uniqueLabel($property->name(), $choices)] = (string) $property->id();
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

    /** @return array<string, mixed> */
    private function dateOptions(string $label): array
    {
        return [
            'label' => new TranslatableMessage($label),
            'widget' => 'single_text',
            'html5' => true,
            'input' => 'datetime_immutable',
            'model_timezone' => date_default_timezone_get(),
            'view_timezone' => $this->timezoneProvider->currentTimezone()->getName(),
            'constraints' => [new Assert\NotNull()],
        ];
    }
}
