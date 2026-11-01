<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Infrastructure\Symfony\Form\Type;

use App\CarbonFootprint\Domain\Model\OrganizationalCarbonFootprint\OrganizationalCarbonFootprintStatus;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprint;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintFilter;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintId;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Property\Domain\Model\Property\PropertyRepository;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;
use App\Shared\Infrastructure\Symfony\Form\DataTransformer\AggregateRootIdTransformer;
use App\Shared\Infrastructure\Symfony\Form\Type\AutocompleteType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class PropertyCarbonFootprintChoiceType extends AbstractType
{
    public function __construct(
        private readonly LoggedAccount $loggedAccount,
        private readonly PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new AggregateRootIdTransformer(PropertyCarbonFootprintId::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $loggedAccount = $this->loggedAccount;
        $account = $loggedAccount();

        if (null === $account) {
            $resolver->setDefaults([
                'choices' => [],
            ]);

            return;
        }

        $footprints = $this->propertyCarbonFootprintRepository->findByFilter(new PropertyCarbonFootprintFilter(
            status: OrganizationalCarbonFootprintStatus::Completed,
            accountId: $account->id(),
            limit: 0,
        ));

        $choices = [];

        foreach ($footprints as $footprint) {
            $choices[$this->label($footprint, $choices)] = (string) $footprint->id();
        }

        $resolver->setDefaults([
            'choices' => $choices,
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteType::class;
    }

    /** @param array<string, string> $choices */
    private function label(PropertyCarbonFootprint $footprint, array $choices): string
    {
        $propertyId = $footprint->propertyId();
        $property = null !== $propertyId ? $this->propertyRepository->find($propertyId) : null;

        $label = null !== $property ? sprintf('%s — %d', $property->name(), $footprint->year()) : (string) $footprint->id();

        $candidate = $label;
        $i = 0;

        while (isset($choices[$candidate])) {
            $candidate = sprintf('%s (%d)', $label, ++$i);
        }

        return $candidate;
    }
}
