<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Form\QuizType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/quiz/new', name: 'app_admin_quiz_new')]
    public function newQuiz(Request $request, EntityManagerInterface $em): Response
    {
        $quiz = new Quiz();

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $quiz->setCreator($this->getUser());
            $em->persist($quiz);
            $em->flush();

            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin/quiz/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/quiz', name: 'app_admin_quiz_list')]
    public function quizList(EntityManagerInterface $em): Response
    {
        $quizzes = $em->getRepository(Quiz::class)->findAll();

        return $this->render('admin/quiz/list.html.twig', [
            'quizzes' => $quizzes
        ]);
    }
}
