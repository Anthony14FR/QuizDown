<?php

namespace App\Controller\Api;

use App\Entity\Quiz;
use App\Repository\QuizRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/quiz', name: 'api_quiz_')]
#[IsGranted('ROLE_USER')]
class QuizApiController extends AbstractController
{
    public function __construct(
        private QuizRepository $quizRepository,
        private SerializerInterface $serializer,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function getAllQuizzes(): JsonResponse
    {
        $quizzes = $this->quizRepository->findAll();

        $json = $this->serializer->serialize($quizzes, 'json', [
            'groups' => ['quiz:read'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function getOneQuiz(Quiz $quiz): JsonResponse
    {
        $json = $this->serializer->serialize($quiz, 'json', [
            'groups' => ['quiz:read', 'quiz:read:full'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
