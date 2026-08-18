<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form;

use App\Payments\Application\Payee\Update\UpdatePayeeRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class UpdatePayeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        PayeeFiscalFields::add($builder);

        $builder->add('saveAndReturn', SubmitType::class, [
            'label' => new TranslatableMessage('Guardar beneficiario'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdatePayeeRequest::class,
        ]);
    }
}
