<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form;

use App\CarbonFootprint\Application\CompanyCarbonFootprint\List\ListCompanyCarbonFootprintsRequest;
use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\Property\Infrastructure\Symfony\Form\Type\CompanyIdType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\BaseFilterType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class ListCompanyCarbonFootprintsType extends BaseFilterType
{
    public function __construct(private readonly LoggedAccount $loggedAccount) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $account = ($this->loggedAccount)();

        $builder
            ->add('companyId', CompanyIdType::class, [
                'label' => new TranslatableMessage('Empresa'),
                'account_id' => $account?->id(),
                'required' => false,
                'placeholder' => new TranslatableMessage('Todas las empresas'),
            ])
            ->add('year', IntegerType::class, [
                'label' => new TranslatableMessage('Año'),
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'label' => new TranslatableMessage('Estado'),
                'required' => false,
                'class' => OrganizationalCarbonFootprintStatus::class,
                'placeholder' => new TranslatableMessage('Todos los estados'),
                'choice_label' => fn(OrganizationalCarbonFootprintStatus $status) => new TranslatableMessage($status->value, [], 'organizationalCarbonFootprintStatusType'),
            ])
        ;

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ListCompanyCarbonFootprintsRequest::class,
        ]);
    }
}
