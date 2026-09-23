<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\CarbonFootprintReport\List\ListCarbonFootprintReportsRequest;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportStatus;
use App\CarbonFootprint\Domain\Model\CarbonFootprintReport\CarbonFootprintReportSubjectType;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListCarbonFootprintReportsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', EnumType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'class' => CarbonFootprintReportStatus::class,
                'placeholder' => new TranslatableMessage('Todos los estados'),
                'choice_label' => fn(CarbonFootprintReportStatus $status) => new TranslatableMessage($status->value, [], 'carbonFootprintReportStatus'),
            ])
            ->add('subjectType', EnumType::class, [
                'label' => new TranslatableMessage('Tipo de huella'),
                'required' => false,
                'class' => CarbonFootprintReportSubjectType::class,
                'placeholder' => new TranslatableMessage('Todos los tipos'),
                'choice_label' => fn(CarbonFootprintReportSubjectType $type) => new TranslatableMessage($type->value, [], 'carbonFootprintReportSubjectType'),
            ])
            ->add('accountId', AccountIdType::class, [
                'label' => new TranslatableMessage('Cuenta'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las cuentas'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListCarbonFootprintReportsRequest::class,
        ]);
    }
}
