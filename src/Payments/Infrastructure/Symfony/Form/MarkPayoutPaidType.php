<?php

declare(strict_types=1);

namespace App\Payments\Infrastructure\Symfony\Form;

use App\Library\Domain\Model\Media\MediaType;
use App\Library\Domain\Model\Media\MediaVisibility;
use App\Library\Infrastructure\Symfony\Form\Type\MediaPickerType;
use App\Payments\Application\Payout\Pay\MarkPayoutPaidRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class MarkPayoutPaidType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'label' => new TranslatableMessage('Referencia de la transferencia'),
                'required' => true,
                'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            ])
            ->add('evidence', MediaPickerType::class, [
                'label' => new TranslatableMessage('Justificante'),
                'required' => false,
                'types' => [MediaType::Document, MediaType::Image],
                'visibility' => MediaVisibility::System,
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Marcar como pagada'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MarkPayoutPaidRequest::class,
        ]);
    }
}
