<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Symfony\Form;

use App\Security\Application\SignatureRequest\List\ListSignatureRequestsRequest;
use App\Security\Infrastructure\Symfony\Form\Type\SignatureRequestStatusType;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListSignatureRequestsType extends BaseFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('signatureStatus', SignatureRequestStatusType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListSignatureRequestsRequest::class,
        ]);
    }
}
