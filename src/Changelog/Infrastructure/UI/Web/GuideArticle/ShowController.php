<?php

declare(strict_types=1);

namespace App\Changelog\Infrastructure\UI\Web\GuideArticle;

use App\Changelog\Application\GuideArticle\View\ViewGuideArticleRequest;
use App\Changelog\Application\GuideArticle\View\ViewGuideArticleService;
use App\Changelog\Domain\Model\GuideArticle\GuideArticleNotFoundException;
use App\Changelog\Domain\Model\GuideArticle\GuideArticlePath;
use App\Changelog\Domain\Model\GuideArticle\InvalidGuideArticleException;
use App\Security\Application\UnauthorizedActionException;
use App\Security\Infrastructure\Symfony\Security\LoggedUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/guide/{path}', name: 'guide_article', requirements: ['path' => '[a-z0-9-]+(/[a-z0-9-]+)*'], methods: ['GET'])]
#[IsGranted('ROLE_USER')]
class ShowController extends AbstractController
{
    public function __invoke(string $path, Request $request, ViewGuideArticleService $viewGuideArticle, LoggedUser $loggedUser): Response
    {
        try {
            $response = $viewGuideArticle(new ViewGuideArticleRequest(new GuideArticlePath($path)), $loggedUser());
        } catch (UnauthorizedActionException $e) {
            throw $this->createAccessDeniedException($e->getMessage());
        } catch (GuideArticleNotFoundException|InvalidGuideArticleException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $template = $request->isXmlHttpRequest() ? '@changelog/guide/_modal.html.twig' : '@changelog/guide/show.html.twig';

        $page = $this->render($template, ['article' => $response->article]);
        $page->headers->set('Cache-Control', 'no-store');

        return $page;
    }
}
