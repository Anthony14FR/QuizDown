<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\PenaltyQuiz;
use App\Entity\Quiz;
use App\Entity\Submission;
use App\Entity\SubmissionAnswer;
use App\Entity\TimedQuiz;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\QuizType;
use App\Repository\CategoryRepository;
use App\Repository\QuizRepository;
use App\Repository\TagRepository;
use App\Security\Voter\QuizVoter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/quiz')]
#[IsGranted('ROLE_USER')]
class QuizController extends AbstractController
{
    public function __construct(
        private QuizRepository $quizRepository,
        private CategoryRepository $categoryRepository,
        private TagRepository $tagRepository,
    ) {
    }

    #[Route('', name: 'app_quiz_index', methods: ['GET'])]
    public function indexQuizzes(Request $request): Response
    {
        $category = $request->query->get('category');
        $tag = $request->query->get('tag');
        $order = $request->query->get('order', 'desc');
        $searchTerm = $request->query->get('q', '');
        $page = max((int) $request->query->get('page', 1), 1);
        $limit = max((int) $request->query->get('limit', 6), 1);

        $totalQuizzes = $this->quizRepository->countFilteredQuizzes($category, $tag, $searchTerm);

        $totalPages = (int) ceil($totalQuizzes / $limit);

        $quizzes = $this->quizRepository->findPaginatedFilteredQuizzes($category, $tag, $order, $searchTerm, $page, $limit);

        $categories = $this->categoryRepository->findAll();
        $tags = $this->tagRepository->findAll();

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizzes,
            'category' => $category,
            'tag' => $tag,
            'order' => $order,
            'searchTerm' => $searchTerm,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    #[Route('/detail/{id}', name: 'app_quiz_detail')]
    public function detail(Quiz $quiz): Response
    {
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment, [
            'action' => $this->generateUrl('app_quiz_comment', ['id' => $quiz->getId()]),
        ]);

        return $this->render('quiz/detail.html.twig', [
            'quiz' => $quiz,
            'comments' => $quiz->getComments(),
            'commentForm' => $commentForm,
        ]);
    }

    #[Route('/new', name: 'app_quiz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $quizData = $request->request->all('quiz');
        $quizType = $quizData['type'] ?? $request->query->get('type', 'base');

        switch ($quizType) {
            case 'timed':
                $quiz = new TimedQuiz();
                break;
            case 'penalty':
                $quiz = new PenaltyQuiz();
                break;
            default:
                $quiz = new Quiz();
        }

        $quiz->setCreator($this->getUser() instanceof User ? $this->getUser() : null);

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($quiz instanceof TimedQuiz) {
                $quiz->setTimeLimit($quizData['timeLimit'] ?? null);
            }
            if ($quiz instanceof PenaltyQuiz) {
                $quiz->setPenaltyPoints($quizData['penaltyPoints'] ?? null);
                $quiz->setTimeLimit($quizData['timeLimit'] ?? null);
            }

            foreach ($quiz->getCategories() as $category) {
                $category->addQuiz($quiz);
            }
            foreach ($quiz->getTags() as $tag) {
                $tag->addQuiz($quiz);
            }

            if ($quiz->getQuestions()->count() < 1) {
                $this->addFlash('error', 'Le quiz doit avoir au moins une question.');

                return $this->redirectToRoute('app_quiz_new');
            }

            foreach ($quiz->getQuestions() as $question) {
                if ('multiple_choice' === $question->getType()) {
                    $correctAnswers = $question->getAnswers()->filter(fn ($a) => $a->isCorrect());
                    if ($correctAnswers->count() < 2) {
                        $this->addFlash('error', 'Les questions à choix multiple doivent avoir au moins deux réponses correctes.');

                        return $this->redirectToRoute('app_quiz_new');
                    }
                }
            }

            $correctAnswers = $quiz->getQuestions()->map(fn ($q) => $q->getAnswers()->filter(fn ($a) => $a->isCorrect())->count());
            if ($correctAnswers->contains(0)) {
                $this->addFlash('error', 'Toutes les questions doivent avoir au moins une réponse correcte.');

                return $this->redirectToRoute('app_quiz_new');
            }

            $quiz->defaultScore = $quizData['defaultScore'] > 0 ? $quizData['defaultScore'] : 1;

            $entityManager->persist($quiz);
            $entityManager->flush();

            $this->addFlash('success', 'Votre quiz a été créé avec succès.');

            return $this->redirectToRoute('app_quiz_index');
        }

        return $this->render('quiz/new.html.twig', [
            'form' => $form,
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quiz_edit', methods: ['GET', 'POST'])]
    public function edit(Quiz $quiz, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(QuizVoter::EDIT, $quiz);

        $originalQuestions = new ArrayCollection();
        foreach ($quiz->getQuestions() as $question) {
            $originalQuestions->add($question);
        }

        $originalAnswers = new ArrayCollection();
        foreach ($quiz->getQuestions() as $question) {
            foreach ($question->getAnswers() as $answer) {
                $originalAnswers->add($answer);
            }
        }

        $form = $this->createForm(QuizType::class, $quiz, [
            'quiz_type' => $quiz->getType(),
            'time_limit' => ($quiz instanceof TimedQuiz || $quiz instanceof PenaltyQuiz)
                                ? $quiz->getTimeLimit()
                                : null,
            'penalty_points' => $quiz instanceof PenaltyQuiz ? $quiz->getPenaltyPoints() : null,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($originalQuestions as $question) {
                if (!$quiz->getQuestions()->contains($question)) {
                    $entityManager->remove($question);
                }
            }

            foreach ($originalAnswers as $answer) {
                $found = false;
                foreach ($quiz->getQuestions() as $question) {
                    if ($question->getAnswers()->contains($answer)) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $entityManager->remove($answer);
                }
            }

            if ($quiz instanceof TimedQuiz) {
                $timeLimit = $form->get('timeLimit')->getData();
                if (null !== $timeLimit) {
                    $quiz->setTimeLimit($timeLimit);
                }
            }

            if ($quiz instanceof PenaltyQuiz) {
                $penaltyPoints = $form->get('penaltyPoints')->getData();
                $timeLimit = $form->get('timeLimit')->getData();

                if (null !== $penaltyPoints) {
                    $quiz->setPenaltyPoints($penaltyPoints);
                }
                if (null !== $timeLimit) {
                    $quiz->setTimeLimit($timeLimit);
                }
            }

            foreach ($quiz->getCategories() as $category) {
                if (!$category->getQuizzes()->contains($quiz)) {
                    $category->addQuiz($quiz);
                }
            }
            foreach ($quiz->getTags() as $tag) {
                if (!$tag->getQuizzes()->contains($quiz)) {
                    $tag->addQuiz($quiz);
                }
            }

            if ($quiz->getQuestions()->count() < 1) {
                $this->addFlash('error', 'Le quiz doit avoir au moins une question.');

                return $this->redirectToRoute('app_quiz_edit', ['id' => $quiz->getId()]);
            }

            foreach ($quiz->getQuestions() as $question) {
                if ('multiple_choice' === $question->getType()) {
                    $correctAnswers = $question->getAnswers()->filter(fn ($a) => $a->isCorrect());
                    if ($correctAnswers->count() < 2) {
                        $this->addFlash('error', 'Les questions à choix multiple doivent avoir au moins deux réponses correctes.');

                        return $this->redirectToRoute('app_quiz_edit', ['id' => $quiz->getId()]);
                    }
                }
            }

            $correctAnswers = $quiz->getQuestions()->map(fn ($q) => $q->getAnswers()->filter(fn ($a) => $a->isCorrect())->count());
            if ($correctAnswers->contains(0)) {
                $this->addFlash('error', 'Toutes les questions doivent avoir au moins une réponse correcte.');

                return $this->redirectToRoute('app_quiz_edit', ['id' => $quiz->getId()]);
            }

            $quiz->defaultScore = $quiz->defaultScore > 0 ? $quiz->defaultScore : 1;

            $entityManager->flush();

            $this->addFlash('success', 'Le quiz a été modifié avec succès.');

            return $this->redirectToRoute('app_quiz_index');
        }

        return $this->render('quiz/edit.html.twig', [
            'form' => $form,
            'quiz' => $quiz,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_quiz_delete', methods: ['POST'])]
    public function delete(Quiz $quiz, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(QuizVoter::DELETE, $quiz);

        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->request->get('_token'))) {
            $entityManager->remove($quiz);
            $entityManager->flush();

            $this->addFlash('success', 'Le quiz a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_quiz_index');
    }

    #[Route('/{id}/play', name: 'app_quiz_play')]
    public function play(Quiz $quiz, SerializerInterface $serializer): Response
    {
        $quizData = json_decode($serializer->serialize($quiz, 'json', [
            'groups' => ['quiz:read:full'],
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            },
        ]), true);

        $quizData['type'] = match (true) {
            $quiz instanceof TimedQuiz => 'timed',
            $quiz instanceof PenaltyQuiz => 'penalty',
            default => 'base',
        };

        if ($quiz instanceof TimedQuiz) {
            $quizData['timeLimit'] = $quiz->getTimeLimit();
        }

        if ($quiz instanceof PenaltyQuiz) {
            $quizData['penaltyPoints'] = $quiz->getPenaltyPoints();
            $quizData['timeLimit'] = $quiz->getTimeLimit();
        }

        return $this->render('quiz/play.html.twig', [
            'quiz' => $quizData,
        ]);
    }

    #[Route('/{id}/submit', name: 'app_quiz_submit', methods: ['POST'])]
    public function submit(Request $request, Quiz $quiz, EntityManagerInterface $em): Response
    {
        $answers = $request->request->all('answers');
        $score = 0;
        $penaltyPoints = 0;
        $currentUser = $this->getUser();

        if ($quiz instanceof PenaltyQuiz) {
            $penaltyPoints = $quiz->getPenaltyPoints() ?? 0;
        }

        foreach ($quiz->getQuestions() as $question) {
            $userAnswer = $answers[$question->getId()] ?? null;
            $answeredCorrectly = false;

            if ('true_false' === $question->getType() || 'single_choice' === $question->getType()) {
                foreach ($question->getAnswers() as $answer) {
                    if ($answer->getId() == $userAnswer && $answer->isCorrect()) {
                        $score += $quiz->getDefaultScore();
                        $answeredCorrectly = true;
                        break;
                    }
                }
            } elseif ('multiple_choice' === $question->getType()) {
                $userAnswers = is_array($userAnswer) ? $userAnswer : [];
                $correctAnswers = $question->getAnswers()->filter(fn ($a) => $a->isCorrect())->map(fn ($a) => $a->getId())->toArray();
                if ($userAnswers == $correctAnswers) {
                    $score += $quiz->getDefaultScore();
                    $answeredCorrectly = true;
                }
            }

            if ($quiz instanceof PenaltyQuiz && !$answeredCorrectly && $penaltyPoints > 0) {
                $score -= $penaltyPoints;
            }
        }

        if ($score < 0) {
            $score = 0;
        }

        $submission = $em->getRepository(Submission::class)->findOneBy([
            'quiz' => $quiz,
            'player' => $currentUser,
        ]);

        if (!$submission) {
            $submission = new Submission();
            $submission->setQuiz($quiz);
            $submission->setPlayer($currentUser instanceof User ? $currentUser : null);
        } else {
            foreach ($submission->getSubmissionAnswers() as $oldAnswer) {
                $em->remove($oldAnswer);
            }
            $submission->getSubmissionAnswers()->clear();
        }

        $submission->setSubmittedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));

        foreach ($quiz->getQuestions() as $question) {
            $userAnswer = $answers[$question->getId()] ?? null;

            $submissionAnswer = new SubmissionAnswer();
            $submissionAnswer->setQuestion($question);
            $submissionAnswer->setSubmission($submission);
            $submissionAnswer->setUserAnswer($userAnswer);

            $isCorrect = false;
            if ('true_false' === $question->getType() || 'single_choice' === $question->getType()) {
                foreach ($question->getAnswers() as $answer) {
                    if ($answer->getId() == $userAnswer && $answer->isCorrect()) {
                        $isCorrect = true;
                        break;
                    }
                }
            } elseif ('multiple_choice' === $question->getType()) {
                $userAnswers = is_array($userAnswer) ? $userAnswer : [];
                $correctAnswers = $question->getAnswers()->filter(fn ($a) => $a->isCorrect())->map(fn ($a) => $a->getId())->toArray();
                if ($userAnswers == $correctAnswers) {
                    $isCorrect = true;
                }
            }

            $submissionAnswer->setIsCorrect($isCorrect);

            $em->persist($submissionAnswer);
        }

        $bestScore = $submission->getScore() ?? 0;
        if ($score > $bestScore) {
            $submission->setScore($score);
        }

        $em->persist($submission);
        $em->flush();

        return $this->redirectToRoute('app_quiz_result', [
            'id' => $submission->getId(),
            'attemptScore' => $score,
        ]);
    }

    #[Route('/result/{id}', name: 'app_quiz_result')]
    public function result(Submission $submission, Request $request): Response
    {
        $attemptScore = $request->query->get('attemptScore');

        return $this->render('quiz/result.html.twig', [
            'submission' => $submission,
            'attemptScore' => $attemptScore,
        ]);
    }

    #[Route('/{id}/comment', name: 'app_quiz_comment', methods: ['POST'])]
    public function addComment(Quiz $quiz, Request $request, EntityManagerInterface $em): Response
    {
        $comment = new Comment();
        $comment->setQuiz($quiz);
        $comment->setAuthor($this->getUser() instanceof User ? $this->getUser() : null);
        $comment->setCreatedAt(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($comment->getContent())) {
                $this->addFlash('error', 'Vous devez être connecté pour commenter.');

                return $this->redirectToRoute('app_quiz_detail', ['id' => $quiz->getId()]);
            }

            if (strlen($comment->getContent()) < 5 || strlen($comment->getContent()) > 200) {
                $this->addFlash('error', 'Le commentaire doit contenir entre 5 et 200 caractères.');

                return $this->redirectToRoute('app_quiz_detail', ['id' => $quiz->getId()]);
            }

            $em->persist($comment);
            $em->flush();
            $this->addFlash('success', 'Commentaire ajouté avec succès');

            return $this->redirectToRoute('app_quiz_detail', ['id' => $quiz->getId()]);
        }

        return $this->redirectToRoute('app_quiz_detail', ['id' => $quiz->getId()]);
    }

    #[Route('/{id}/comment/delete', name: 'app_comment_delete', methods: ['POST'])]
    public function deleteComment(
        Comment $comment,
        EntityManagerInterface $entityManager,
        Request $request,
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $quizId = $comment->getQuiz()->getId();

            $entityManager->remove($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Commentaire supprimé avec succès.');

            return $this->redirectToRoute('app_quiz_detail', ['id' => $quizId]);
        }

        return $this->redirectToRoute('app_quiz_detail', ['id' => $comment->getQuiz()->getId()]);
    }
}
