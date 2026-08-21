<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\TripCalculationLink\UpdateScope\UpdateTripCalculationLinkScopeRequest;
use App\CarbonFootprint\Domain\Model\TripCalculationLink\TripCalculationScope;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<UpdateTripCalculationLinkScopeRequest> */
class UpdateTripCalculationLinkScopeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('scope', EnumType::class, [
                'label' => false,
                'class' => TripCalculationScope::class,
                'choice_label' => static fn(TripCalculationScope $scope): TranslatableMessage => match ($scope) {
                    TripCalculationScope::Both => new TranslatableMessage('Desplazamientos y pernoctaciones'),
                    TripCalculationScope::Transport => new TranslatableMessage('Solo desplazamientos'),
                    TripCalculationScope::OvernightStay => new TranslatableMessage('Solo pernoctaciones'),
                },
                'expanded' => true,
                'required' => true,
            ])
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Aplicar'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateTripCalculationLinkScopeRequest::class,
        ]);
    }
}
