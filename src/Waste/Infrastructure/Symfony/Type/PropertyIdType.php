<?php

namespace App\Waste\Infrastructure\Symfony\Type;

use Symfony\Component\Form\AbstractType;
use App\Waste\Domain\Model\Property\Property;
use App\Waste\Domain\Model\Property\PropertyId;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use App\Waste\Domain\Model\Property\PropertyFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Waste\Domain\Model\Property\PropertyRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Security\Infrastructure\Symfony\Security\LoggedAccount;

class PropertyIdType extends AbstractType
{
    public function __construct(
        private LoggedAccount $loggedAccount, 
        private PropertyRepository $propertyRepository
    ) {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(
            new CallbackTransformer(
                function (?PropertyId $propertyId): string {
                    return $propertyId ? (string) $propertyId : '';
                },
                function (?string $propertyId): PropertyId|null {
                    return (null == $propertyId || empty($propertyId)) ? null : new PropertyId($propertyId);
                }
            )
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $loggedAccount = $this->loggedAccount;

        $properties = $this->propertyRepository->findByFilter(
            new PropertyFilter(
                account: $loggedAccount()
            )
        );

        $resolver->setDefaults(array(
            'choices' => $this->getChoices($properties)
        ));
    }
    
    public function getParent()
    {
        return ChoiceType::class;
    }

    private function getChoices($properties)
    {
        if (null == $properties || count($properties) == 0) {
            return [];
        }

        $choices = [];

        foreach ($properties as $property) {
            $name = $property->name();

            $i = 0;

            while (isset($choices[$name])) {
                $name = sprintf("%s (%d)", $property->name(), ++$i);
            }

            $choices[$name] = (string) $property->id();
        }

        return $choices;
    }
}