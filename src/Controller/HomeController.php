<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\QuizRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuizRepository $quizRepository,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
    ) {
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        if (!$this->getUser()) {
            return $this->render('home/landing.html.twig');
        }

        if ($this->isGranted('ROLE_BANNED')) {
            return $this->redirectToRoute('app_logout');
        }

        $stats = [
            'total_quizzes' => $this->quizRepository->count([]),
            'total_users' => $this->entityManager->getRepository(User::class)->count([]),
            'total_submissions' => $this->entityManager->getRepository(Submission::class)->count([]),
            'total_categories' => $this->categoryRepository->count([]),
            'total_tags' => $this->tagRepository->count([]),
            'avg_score' => $this->entityManager->getRepository(Submission::class)->createQueryBuilder('s')
                    ->select('AVG(s.score)')
                    ->getQuery()
                    ->getSingleScalarResult() ?? 0,
        ];

        return $this->render('home/index.html.twig', [
            'stats' => $stats,
            'popular_quizzes' => $this->quizRepository->findMostPlayed(),
        ]);
    }
}
