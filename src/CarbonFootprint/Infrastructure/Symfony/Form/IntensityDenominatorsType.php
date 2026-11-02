<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<object> */
class IntensityDenominatorsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('occupiedRooms', IntegerType::class, $this->integerOptions(new TranslatableMessage('Habitaciones ocupadas en el año')))
            ->add('stays', IntegerType::class, $this->integerOptions(new TranslatableMessage('Pernoctaciones')))
            ->add('builtAreaM2', NumberType::class, $this->decimalOptions(new TranslatableMessage('Superficie construida (m²)')))
            ->add('coversServed', IntegerType::class, $this->integerOptions(new TranslatableMessage('Cubiertos servidos')))
            ->add('averageHeadcount', NumberType::class, $this->decimalOptions(new TranslatableMessage('Plantilla media')))
            ->add('revenueEur', NumberType::class, $this->decimalOptions(new TranslatableMessage('Facturación (€)')))
            ->add('customersServed', IntegerType::class, $this->integerOptions(new TranslatableMessage('Clientes atendidos')))
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar datos de actividad'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('data_class');
    }

    /** @return array<string, mixed> */
    private function integerOptions(TranslatableMessage $label): array
    {
        return [
            'label' => $label,
            'required' => false,
            'constraints' => [
                new Assert\Positive(),
                new Assert\LessThanOrEqual(2147483647),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function decimalOptions(TranslatableMessage $label): array
    {
        return [
            'label' => $label,
            'required' => false,
            'scale' => 2,
            'input' => 'string',
            'constraints' => [
                new Assert\Positive(),
            ],
        ];
    }
}
