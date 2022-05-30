<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ConferenceController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(Environment $twig, ConferenceRepository $confRep): Response
    {
        return new Response($twig->render('conference/index.html.twig', [
            'conferences' => $confRep->findAll(),
        ]));
    }

    #[Route('/conference/{id}', name: 'conference')]
    public function show(Request $request, Environment $twig, Conference $conference, CommentRepository $commentRep): Response {
        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRep->getCommentPaginator($conference, $offset);
        return new Response($twig->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::COMMENT_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::COMMENT_PER_PAGE),
        ]));
    }
}
