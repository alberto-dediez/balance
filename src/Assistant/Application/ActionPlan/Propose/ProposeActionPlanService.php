<?php

declare(strict_types=1);

namespace App\Assistant\Application\ActionPlan\Propose;

use App\Assistant\Application\ActionPlan\ActionPlanAuthorizer;
use App\Assistant\Application\Conversation\ConversationAuthorizer;
use App\Assistant\Application\Engine\Toolbox;
use App\Assistant\Domain\Model\ActionPlan\ActionPlan;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanFilter;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanId;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanRepository;
use App\Assistant\Domain\Model\ActionPlan\ActionPlanStatus;
use App\Assistant\Domain\Model\ActionPlan\InvalidPlanException;
use App\Assistant\Domain\Model\ActionPlan\ProposedArgument;
use App\Assistant\Domain\Model\ActionPlan\ProposedStep;
use App\Assistant\Domain\Model\Conversation\ConversationBusyException;
use App\Assistant\Domain\Model\Conversation\ConversationRepository;
use App\Security\Application\Action;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\Assistant\InvalidToolArgumentsException;
use App\Shared\Application\Assistant\ToolArgumentsFactory;
use App\Shared\Application\Assistant\ToolContext;
use App\Shared\Application\Assistant\ToolMode;
use App\Shared\Application\Assistant\ToolParameterType;
use App\Shared\Application\Assistant\WriteAssistantTool;
use App\Shared\Application\Clock\Clock;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;

class ProposeActionPlanService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private ActionPlanRepository $repository,
        private ConversationRepository $conversations,
        private ActionPlanAuthorizer $authorizer,
        private ConversationAuthorizer $conversationAuthorizer,
        private Toolbox $toolbox,
        private Clock $clock,
    ) {}

    public function __invoke(ProposeActionPlanRequest $request, User $user): ProposeActionPlanResponse
    {
        $plan = new ActionPlan(ActionPlanId::generate());

        $this->authorizer->denyUnlessCanDoThat($plan, Action::Register, $user);

        $conversation = $this->conversations->find($request->conversationId);

        if (null === $conversation) {
            throw new \InvalidArgumentException('Conversation not found');
        }

        $this->conversationAuthorizer->denyUnlessCanDoThat($conversation, Action::Edit, $user);

        $live = $this->repository->totalResultsByFilter(new ActionPlanFilter(conversationId: $conversation->id(), statuses: [ActionPlanStatus::Proposed, ...ActionPlanStatus::active()], limit: 0));

        if ($live > 0) {
            throw new ConversationBusyException();
        }

        $context = new ToolContext($this->clock->now(), $this->timezone($request->timezone));
        $errors = [];
        $steps = [];

        if ([] === $request->steps) {
            $errors[] = 'El plan necesita al menos un paso.';
        }

        foreach ($request->steps as $index => $step) {
            $position = $index + 1;
            $proposed = $this->step($position, $step, $user, $context, $errors);

            if (null !== $proposed) {
                $steps[] = $proposed;
            }
        }

        if ([] !== $errors) {
            throw new InvalidPlanException($errors);
        }

        $plan->propose($request->conversationId, $user->id(), $request->summary, $context->timezone->getName(), $steps, $context->now);

        $this->repository->add($plan);

        return new ProposeActionPlanResponse($plan->id());
    }

    public function isHandled(Command $command): bool
    {
        return $command instanceof ProposeActionPlanRequest;
    }

    /**
     * @param array{tool: string, arguments: list<array{name: string, value: string, fromStep: ?int}>} $step
     * @param list<string>                                                                             $errors
     */
    private function step(int $position, array $step, User $user, ToolContext $context, array &$errors): ?ProposedStep
    {
        $tool = $this->toolbox->get($step['tool']);

        if (!$tool instanceof WriteAssistantTool || ToolMode::Write !== $tool->mode() || !$tool->isAvailableTo($user)) {
            $errors[] = sprintf('Paso %d: la herramienta «%s» no es una herramienta de escritura disponible.', $position, $step['tool']);

            return null;
        }

        $types = [];

        foreach ($tool->parameters() as $parameter) {
            $types[$parameter->name] = $parameter->type;
        }

        $raw = [];
        $deferred = [];
        $stepErrors = [];

        foreach ($step['arguments'] as $argument) {
            $name = $argument['name'];

            if (null !== $argument['fromStep']) {
                if ($argument['fromStep'] < 1 || $argument['fromStep'] >= $position) {
                    $stepErrors[] = sprintf('Paso %d: «%s» solo puede usar el resultado de un paso anterior.', $position, $name);
                } elseif (ToolParameterType::Id !== ($types[$name] ?? null)) {
                    $stepErrors[] = sprintf('Paso %d: «%s» no es un identificador y no puede venir de otro paso.', $position, $name);
                } else {
                    $deferred[$name] = $argument['fromStep'];
                }

                continue;
            }

            $raw[$name][] = $argument['value'];
        }

        $raw = array_map(static fn(array $values): string|array => 1 === count($values) ? $values[0] : $values, $raw);

        try {
            $arguments = ToolArgumentsFactory::create($tool->parameters(), $raw, $context, array_keys($deferred));
        } catch (InvalidToolArgumentsException $exception) {
            foreach ($exception->errors() as $error) {
                $stepErrors[] = sprintf('Paso %d: %s', $position, $error);
            }
        }

        if ([] !== $stepErrors || !isset($arguments)) {
            array_push($errors, ...$stepErrors);

            return null;
        }

        try {
            $displays = $tool->preview($arguments, $user, $context)->displays;
        } catch (InvalidToolArgumentsException $exception) {
            foreach ($exception->errors() as $error) {
                $errors[] = sprintf('Paso %d: %s', $position, $error);
            }

            return null;
        } catch (UnauthorizedActionException|\InvalidArgumentException|\DomainException $exception) {
            $errors[] = sprintf('Paso %d: %s', $position, $exception->getMessage());

            return null;
        }

        $proposedArguments = [];

        foreach ($arguments->all() as $name => $values) {
            foreach ($values as $value) {
                $proposedArguments[] = new ProposedArgument($name, $value, $displays[$name] ?? null);
            }
        }

        foreach ($deferred as $name => $fromStep) {
            $proposedArguments[] = new ProposedArgument($name, '', null, $fromStep);
        }

        return new ProposedStep($tool->name(), $tool->label(), $proposedArguments);
    }

    private function timezone(string $timezone): \DateTimeZone
    {
        try {
            return new \DateTimeZone('' === trim($timezone) ? 'UTC' : $timezone);
        } catch (\Exception) {
            return new \DateTimeZone('UTC');
        }
    }
}
