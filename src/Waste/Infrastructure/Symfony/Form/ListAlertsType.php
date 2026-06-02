<?php

declare(strict_types=1);

namespace App\Waste\Infrastructure\Symfony\Form;

use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use App\Waste\Application\Alert\List\ListAlertsRequest;
use App\Waste\Domain\Model\Alert\AlertStatus;
use App\Waste\Domain\Model\Alert\AlertType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListAlertsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'class' => AlertType::class,
                'label' => new TranslatableMessage('Tipo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los tipos'),
            ])
            ->add('status', EnumType::class, [
                'class' => AlertStatus::class,
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los estados'),
            ])
            ->add('from', DateType::class, [
                'label' => new TranslatableMessage('Desde'),
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('to', DateType::class, [
                'label' => new TranslatableMessage('Hasta'),
                'required' => false,
                'widget' => 'single_text',
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListAlertsRequest::class,
        ]);
    }
}
