<?php

declare(strict_types=1);

namespace App\EventOperations\Infrastructure\Symfony\Form;

use App\EventOperations\Application\Event\Update\UpdateEventRequest;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventSectorType;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventSizeType;
use App\EventOperations\Infrastructure\Symfony\Form\Type\EventTypologyType;
use App\Property\Infrastructure\Symfony\Form\Type\CompanyIdType;
use App\Property\Infrastructure\Symfony\Form\Type\DestinationIdType;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyIdType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/** @extends AbstractType<mixed> */
class UpdateEventType extends AbstractType
{
    use EventDateRangeValidationTrait;

    public function __construct(private readonly LoggedAccount $loggedAccount) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $account = ($this->loggedAccount)();

        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('location', DestinationIdType::class, [
                'label' => new TranslatableMessage('Ubicación'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('propertyId', PropertyIdType::class, [
                'label' => new TranslatableMessage('Instalación'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin instalación'),
            ])
            ->add('companyId', CompanyIdType::class, [
                'label' => new TranslatableMessage('Empresa'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Sin empresa'),
                'account_id' => $account?->id(),
            ])
            ->add('size', EventSizeType::class, [
                'label' => new TranslatableMessage('Tamaño del evento'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('estimatedAttendees', IntegerType::class, [
                'label' => new TranslatableMessage('Nº de asistentes (estimado)'),
                'required' => false,
            ])
            ->add('sector', EventSectorType::class, [
                'label' => new TranslatableMessage('Sector del evento'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('typology', EventTypologyType::class, [
                'label' => new TranslatableMessage('Tipología del evento'),
                'required' => true,
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('assemblyStartDate', DateType::class, [
                'label' => new TranslatableMessage('Inicio del montaje'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('assemblyEndDate', DateType::class, [
                'label' => new TranslatableMessage('Fin del montaje'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('celebrationStartDate', DateType::class, [
                'label' => new TranslatableMessage('Inicio de la celebración'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('celebrationEndDate', DateType::class, [
                'label' => new TranslatableMessage('Fin de la celebración'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('breakdownStartDate', DateType::class, [
                'label' => new TranslatableMessage('Inicio del desmontaje'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new Assert\NotNull(),
                ],
            ])
            ->add('breakdownEndDate', DateType::class, [
                'label' => new TranslatableMessage('Fin del desmontaje'),
                'required' => true,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [
                    new Assert\NotNull(),
                ],
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
            'data_class' => UpdateEventRequest::class,
            'constraints' => [new Assert\Callback([self::class, 'validateDateRangesForUpdate'])],
        ]);
    }

    public static function validateDateRangesForUpdate(UpdateEventRequest $request, ExecutionContextInterface $context): void
    {
        self::validateDateRanges(
            $request->assemblyStartDate,
            $request->assemblyEndDate,
            $request->celebrationStartDate,
            $request->celebrationEndDate,
            $request->breakdownStartDate,
            $request->breakdownEndDate,
            $context,
        );
    }
}
