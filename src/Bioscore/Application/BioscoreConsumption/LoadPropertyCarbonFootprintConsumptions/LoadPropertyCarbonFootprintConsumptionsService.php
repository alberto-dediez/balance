<?php

declare(strict_types=1);

namespace App\Bioscore\Application\BioscoreConsumption\LoadPropertyCarbonFootprintConsumptions;

use App\Bioscore\Application\BioscoreConsumption\BioscoreConsumptionAnswersBuilder;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionGateway;
use App\Bioscore\Domain\Model\BioscoreConsumption\BioscoreConsumptionsQuery;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\PropertyCarbonFootprintAuthorizer;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ReplaceScopeAnswers\ReplacePropertyCarbonFootprintScopeAnswersRequest;
use App\CarbonFootprint\Application\PropertyCarbonFootprint\ReplaceScopeAnswers\ReplacePropertyCarbonFootprintScopeAnswersService;
use App\CarbonFootprint\Domain\Model\PropertyCarbonFootprint\PropertyCarbonFootprintRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandResponse;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class LoadPropertyCarbonFootprintConsumptionsService implements CommandHandler
{
    use RegisterHandlerTrait;

    public function __construct(
        private PropertyCarbonFootprintRepository $propertyCarbonFootprintRepository,
        private PropertyCarbonFootprintAuthorizer $authorizer,
        private BioscoreConsumptionGateway $gateway,
        private BioscoreConsumptionAnswersBuilder $answersBuilder,
        private ReplacePropertyCarbonFootprintScopeAnswersService $replaceAnswers,
    ) {}

    public function __invoke(LoadPropertyCarbonFootprintConsumptionsRequest $request, User $user): LoadPropertyCarbonFootprintConsumptionsResponse
    {
        $propertyCarbonFootprint = $this->propertyCarbonFootprintRepository->find($request->propertyCarbonFootprintId);

        if (null === $propertyCarbonFootprint) {
            throw new \InvalidArgumentException('PropertyCarbonFootprint not found');
        }

        $this->authorizer->denyUnlessCanDoThat($propertyCarbonFootprint, SecurityAction::Edit, $user);

        $accountId = $propertyCarbonFootprint->accountId()
            ?? throw new \InvalidArgumentException('PropertyCarbonFootprint has no account');
        $propertyId = $propertyCarbonFootprint->propertyId()
            ?? throw new \InvalidArgumentException('PropertyCarbonFootprint has no property');

        $results = $this->gateway->fetch(new BioscoreConsumptionsQuery($accountId, [$propertyId], $propertyCarbonFootprint->year()));

        $entry = null;
        foreach ($results as $result) {
            if ($result->propertyId->equals($propertyId)) {
                $entry = $result;

                break;
            }
        }

        if (null === $entry) {
            return new LoadPropertyCarbonFootprintConsumptionsResponse(resolved: false);
        }

        $answers = $this->answersBuilder->build($request->scope, $entry);

        $replaced = ($this->replaceAnswers)(
            new ReplacePropertyCarbonFootprintScopeAnswersRequest($propertyCarbonFootprint->id(), $request->scope, $answers->sectionAnswers),
            $user,
        );

        return new LoadPropertyCarbonFootprintConsumptionsResponse(
            resolved: true,
            activities: $replaced->activities,
            skipped: $answers->skipped,
            includedSources: $replaced->includedSources,
        );
    }

    public function handle(Command $command, User $user): ?CommandResponse
    {
        assert($command instanceof LoadPropertyCarbonFootprintConsumptionsRequest);

        return $this->__invoke($command, $user);
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof LoadPropertyCarbonFootprintConsumptionsRequest;
    }
}
