<?php

declare(strict_types=1);

namespace App\Changelog\Application\GuideArticle\List;

use App\Changelog\Application\GuideArticle\GuideArticleAuthorizer;
use App\Changelog\Domain\Model\GuideArticle\GuideArticle;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleFilter;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleRepository;
use App\Security\Application\Action;
use App\Security\Domain\Model\User\User;

class ListGuideArticlesService
{
    public function __construct(
        private readonly GuideArticleRepository $repository,
        private readonly GuideArticleAuthorizer $authorizer,
    ) {}

    public function __invoke(ListGuideArticlesRequest $request, User $user): ListGuideArticlesResponse
    {
        $profile = $this->authorizer->profileOf($user);

        $this->authorizer->denyUnlessCanDoThat(GuideArticle::placeholder($profile), Action::List, $user);

        $text = null === $request->text ? null : trim($request->text);
        $text = '' === $text ? null : $text;

        $filter = new GuideArticleFilter($profile, $text);
        $filter->limit = max(1, min(50, $request->limit));

        return new ListGuideArticlesResponse(
            array_map(
                static fn(GuideArticle $article): GuideArticleMatch => new GuideArticleMatch($article, null === $text ? null : $article->excerpt($text)),
                $this->repository->findByFilter($filter),
            ),
            $this->repository->totalResultsByFilter($filter),
        );
    }
}
