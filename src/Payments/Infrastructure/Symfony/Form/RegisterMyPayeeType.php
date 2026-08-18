<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form;

use App\Payments\Application\Payee\Register\RegisterPayeeRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<mixed> */
class RegisterMyPayeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        PayeeFiscalFields::add($builder, includeNotes: false);

        $builder->add('register', SubmitType::class, [
            'label' => new TranslatableMessage('Guardar datos de cobro'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterPayeeRequest::class,
        ]);
    }
}
