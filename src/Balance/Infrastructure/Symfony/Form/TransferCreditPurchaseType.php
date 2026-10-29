<?php

declare(strict_types=1);

namespace App\Balance\Infrastructure\Symfony\Form;

use App\Balance\Application\CreditPurchase\Transfer\TransferCreditPurchaseRequest;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/** @extends AbstractType<mixed> */
class TransferCreditPurchaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destinationAccountIds', AccountIdType::class, [
                'label' => new TranslatableMessage('Cuentas de destino'),
                'multiple' => true,
                'required' => true,
                'placeholder' => new TranslatableMessage('Selecciona las cuentas'),
                'empty_data' => [],
                'excluded_account_id' => $options['source_account_id'],
                'constraints' => [
                    new Assert\Count(min: 1),
                ],
            ])
            ->add('tonnes', NumberType::class, [
                'label' => new TranslatableMessage('Toneladas a transferir'),
                'help' => new TranslatableMessage('Déjalo vacío para repartir todo el remanente. Las toneladas se reparten a partes iguales entre las cuentas seleccionadas; lo que no se pueda dividir se queda en este lote o, si se reparte todo el remanente, en la última cuenta.'),
                'required' => false,
                'scale' => 4,
                'constraints' => [
                    new Assert\Positive(),
                ],
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Transferir crédito'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferCreditPurchaseRequest::class,
            'source_account_id' => null,
        ]);

        $resolver->setAllowedTypes('source_account_id', ['null', AccountId::class]);
    }
}
