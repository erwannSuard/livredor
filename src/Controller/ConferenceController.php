<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ConferenceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class ConferenceController extends AbstractController
{
    private $twig;
    private $entityManager;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }


    // ------------   Homepage   ------------------------------------
    #[Route('/', name: 'homepage')]
    public function index(Environment $twig, ConferenceRepository $confRep): Response
    {
        // dump($confRep->findAll());
        return new Response($twig->render('conference/index.html.twig', [
            'conferences' => $confRep->findAll(),
        ]));
    }

    // ------------------------------------------------





    // ------------   Show Conference   ---------------------------------
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(Request $request, Environment $twig, Conference $conference, CommentRepository $commentRep, string $photoDir): Response {


        //Instance commentaire
        $comment = new Comment();
        //Instance de son formulaire
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);
            //Traitement photo formulaire
            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                try {
                    $photo->move($photoDir, $filename);
                }   
                catch (FileException $e) {
                    dd($e);
                }
                $comment->setPhotoFilename($filename);
            }
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }
        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRep->getCommentPaginator($conference, $offset);
        return new Response($twig->render('conference/show.html.twig', [
            'conference' => $conference,
            'photoDir' => $photoDir,  // ATTENTION, INITIATIVE PERSO
            'comments' => $paginator,
            'comment_form' => $form->createView(), // Méthode d'AbstractController pour créer les formulaires
            'previous' => $offset - CommentRepository::COMMENT_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::COMMENT_PER_PAGE),
        ]));
    }
    // ---------------------------------------------
}
