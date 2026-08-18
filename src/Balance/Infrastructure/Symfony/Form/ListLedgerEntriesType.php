<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\LedgerEntry\List\ListLedgerEntriesRequest;
use App\Balance\Infrastructure\Symfony\Form\Type\LedgerBeneficiaryType;
use App\Balance\Infrastructure\Symfony\Form\Type\LedgerEntryTypeType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListLedgerEntriesType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', LedgerEntryTypeType::class, [
                'label' => new TranslatableMessage('Tipo'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('beneficiary', LedgerBeneficiaryType::class, [
                'label' => new TranslatableMessage('Destino'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos'),
            ])
            ->add('from', DateType::class, [
                'label' => new TranslatableMessage('Desde'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('to', DateType::class, [
                'label' => new TranslatableMessage('Hasta'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListLedgerEntriesRequest::class,
        ]);
    }
}
