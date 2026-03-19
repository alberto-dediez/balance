<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form\Type;

use App\Property\Domain\Model\Property\ExternalId;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ExternalIdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hotelbeds', TextType::class, [
                'label' => new TranslatableMessage('Hotelbeds ID'),
                'setter' => function (ExternalId &$externalId, ?string $value, FormInterface $form): void {
                    $externalId = new ExternalId(
                        hotelbeds: $value,
                        restel: $externalId->restel(),
                        bioscore: $externalId->bioscore(),
                    );
                },
                'required' => false,
            ])
            ->add('restel', TextType::class, [
                'label' => new TranslatableMessage('Restel ID'),
                'setter' => function (ExternalId &$externalId, ?string $value, FormInterface $form): void {
                    $externalId = new ExternalId(
                        hotelbeds: $externalId->hotelbeds(),
                        restel: $value,
                        bioscore: $externalId->bioscore(),
                    );
                },
                'required' => false,
            ])
            ->add('bioscore', TextType::class, [
                'label' => new TranslatableMessage('Bioscore ID'),
                'setter' => function (ExternalId &$externalId, ?string $value, FormInterface $form): void {
                    $externalId = new ExternalId(
                        hotelbeds: $externalId->hotelbeds(),
                        restel: $externalId->restel(),
                        bioscore: $value,
                    );
                },
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ExternalId::class,
            'admin' => false,
        ]);
    }
}
