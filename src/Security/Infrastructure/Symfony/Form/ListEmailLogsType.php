<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\EmailLog\List\ListEmailLogsRequest;
use App\Security\Domain\Model\EmailLog\EmailLogStatus;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListEmailLogsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recipient', TextType::class, [
                'label' => new TranslatableMessage('Destinatario'),
                'required' => false,
            ])
            ->add('subject', TextType::class, [
                'label' => new TranslatableMessage('Asunto'),
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'class' => EmailLogStatus::class,
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todos los estados'),
                'choice_label' => fn(EmailLogStatus $status): string => $status->value,
                'choice_translation_domain' => 'emailLogStatus',
            ])
            ->add('provider', TextType::class, [
                'label' => new TranslatableMessage('Proveedor'),
                'required' => false,
            ])
            ->add('sentFrom', DateType::class, [
                'label' => new TranslatableMessage('Enviado desde'),
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('sentUntil', DateType::class, [
                'label' => new TranslatableMessage('Enviado hasta'),
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
            'data_class' => ListEmailLogsRequest::class,
        ]);
    }
}
