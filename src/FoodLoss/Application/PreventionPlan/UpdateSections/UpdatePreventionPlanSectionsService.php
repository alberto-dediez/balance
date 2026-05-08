<?php

declare(strict_types=1);

namespace App\FoodLoss\Application\PreventionPlan\UpdateSections;

use App\FoodLoss\Application\PreventionPlan\PreventionPlanAuthorizer;
use App\FoodLoss\Domain\Model\PreventionPlan\PreventionPlanRepository;
use App\Security\Application\Action as SecurityAction;
use App\Security\Domain\Model\User\User;
use App\Shared\Application\CommandBus\Command;
use App\Shared\Application\CommandBus\CommandHandler;
use App\Shared\Application\CommandBus\CommandInvokeHandlerTrait;
use App\Shared\Application\CommandBus\RegisterHandlerTrait;
use App\Shared\Domain\Model\Locale\LocaleGetter;

class UpdatePreventionPlanSectionsService implements CommandHandler
{
    use RegisterHandlerTrait;
    use CommandInvokeHandlerTrait;

    public function __construct(
        private PreventionPlanRepository $preventionPlanRepository,
        private PreventionPlanAuthorizer $authorizer,
    ) {}

    public function isHandled(Command $command): bool
    {
        return $command instanceof UpdatePreventionPlanSectionsRequest;
    }

    public function __invoke(UpdatePreventionPlanSectionsRequest $request, User $user): UpdatePreventionPlanSectionsResponse
    {
        $preventionPlan = $this->preventionPlanRepository->find($request->preventionPlanId);

        if (null === $preventionPlan) {
            throw new \InvalidArgumentException('Prevention plan not found');
        }

        $this->authorizer->denyUnlessCanDoThat($preventionPlan, SecurityAction::Edit, $user);

        $version = $preventionPlan->currentPlanVersion();
        $sections = $version->sections()->toArray();

        foreach ($sections as $section) {
            $found = false;
            foreach ($request->sections as $sectionData) {
                if (null !== $sectionData->sectionId && $section->id()->equals($sectionData->sectionId)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $preventionPlan->removeSection($section->id());
            }
        }

        $maxOrder = 0;
        foreach ($sections as $section) {
            $maxOrder = max($maxOrder, $section->sectionOrder());
        }

        $locale = LocaleGetter::locale();
        $order = 0;
        foreach ($request->sections as $sectionData) {
            $preventionPlan->addSection(
                title: $sectionData->title,
                content: $sectionData->content,
                order: ++$order,
                locale: $locale,
                sectionId: $sectionData->sectionId,
            );
        }

        $this->preventionPlanRepository->add($preventionPlan);

        return new UpdatePreventionPlanSectionsResponse(
            preventionPlanId: $preventionPlan->id(),
        );
    }
}
