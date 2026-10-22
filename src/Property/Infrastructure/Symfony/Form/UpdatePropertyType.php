<?php

declare(strict_types=1);

namespace App\Property\Infrastructure\Symfony\Form;

use App\Property\Application\Property\Update\UpdatePropertyRequest;
use App\Property\Infrastructure\Symfony\Form\Type\CompanyIdType;
use App\Property\Infrastructure\Symfony\Form\Type\ExternalIdType;
use App\Property\Infrastructure\Symfony\Form\Type\OperatingRegimeType;
use App\Property\Infrastructure\Symfony\Form\Type\PropertyInfoType;
use App\Security\Domain\Model\Account\AccountId;
use App\Security\Infrastructure\Symfony\Form\Type\AccountIdType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

/** @extends AbstractType<UpdatePropertyRequest> */
class UpdatePropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var UpdatePropertyRequest|null $data */
        $data = $options['data'] ?? null;
        $accountId = $data?->accountId;

        $builder
            ->add('name', TextType::class, [
                'label' => new TranslatableMessage('Nombre'),
                'required' => true,
            ])
            ->add('info', PropertyInfoType::class, [
                'label' => new TranslatableMessage('Información'),
            ])
            ->add('numberOfEmployees', IntegerType::class, [
                'label' => new TranslatableMessage('Nº de empleados'),
                'required' => false,
            ])
            ->add('dailyClients', IntegerType::class, [
                'label' => new TranslatableMessage('Nº de clientes diarios'),
                'required' => false,
            ])
            ->add('operatingRegime', OperatingRegimeType::class, [
                'label' => new TranslatableMessage('Régimen de explotación'),
                'required' => true,
                'help' => new TranslatableMessage('Las propiedades franquiciadas o arrendadas a terceros no se incluyen en los alcances 1 y 2 de la huella de empresa: se calculan en su alcance 3.'),
            ])
        ;

        if ($accountId instanceof AccountId) {
            $builder
                ->add('companyId', CompanyIdType::class, [
                    'label' => new TranslatableMessage('Empresa'),
                    'required' => false,
                    'placeholder' => new TranslatableMessage('Sin empresa'),
                    'account_id' => $accountId,
                ]);
        }

        if ($options['admin']) {
            $builder
                ->add('accountId', AccountIdType::class, [
                    'label' => new TranslatableMessage('Cuenta'),
                    'required' => false,
                    'placeholder' => new TranslatableMessage('Sin cuenta'),
                ])
                ->add('externalId', ExternalIdType::class, [
                    'label' => new TranslatableMessage('Identificadores externos'),
                ])
            ;
        }

        $builder
            ->add('save', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar'),
            ])
            ->add('saveAndReturn', SubmitType::class, [
                'label' => new TranslatableMessage('Guardar y salir'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdatePropertyRequest::class,
            'admin' => false,
        ]);
    }
}
