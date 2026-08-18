<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form;

use App\Payments\Application\Payee\Register\RegisterPayeeRequest;
use App\Payments\Infrastructure\Symfony\Form\Type\PayeeSubjectIdType;
use App\Payments\Infrastructure\Symfony\Form\Type\PayeeTypeType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class RegisterPayeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', PayeeTypeType::class, [
                'label' => new TranslatableMessage('Tipo de beneficiario'),
                'required' => true,
                'constraints' => [new Assert\NotNull()],
            ])
            ->add('subjectId', PayeeSubjectIdType::class, [
                'label' => new TranslatableMessage('Cuenta o proyecto'),
                'required' => true,
                'constraints' => [new Assert\NotBlank()],
            ])
        ;

        PayeeFiscalFields::add($builder);

        $builder->add('register', SubmitType::class, [
            'label' => new TranslatableMessage('Crear beneficiario'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterPayeeRequest::class,
        ]);
    }
}
