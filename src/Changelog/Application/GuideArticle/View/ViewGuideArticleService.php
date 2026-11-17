<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle\View;

use App\Changelog\Application\GuideArticle\GuideArticleAuthorizer;
use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleNotFoundException;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ViewGuideArticleService
{
    public function __construct(
        private readonly GuideArticleRepository $repository,
        private readonly GuideArticleAuthorizer $authorizer,
    ) {}

    public function __invoke(ViewGuideArticleRequest $request, User $user): ViewGuideArticleResponse
    {
        $profile = $this->authorizer->profileOf($user);

        $this->authorizer->denyUnlessCanDoThat(GuideArticle::placeholder($profile), Action::View, $user);

        $article = $this->repository->find($profile, $request->path);

        if (!$article instanceof GuideArticle) {
            throw new GuideArticleNotFoundException($request->path);
        }

        return new ViewGuideArticleResponse($article);
    }
}
